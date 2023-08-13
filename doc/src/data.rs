use std::{
    collections::{BTreeMap, BTreeSet},
    ops,
    rc::Rc,
};

use anyhow::Context as _;
use futures::{stream::FuturesUnordered, StreamExt};
use gloo::net::http;
use serde::Deserialize;

pub const SOURCE_LIST_HEADER: &str = "=== InfoAPI schema list ===";

async fn fetch_sources() -> anyhow::Result<Vec<String>> {
    let resp = http::Request::get("static/sources.txt")
        .send()
        .await
        .context("HTTP")?
        .text()
        .await
        .context("parse result")?;

    let mut lines = resp.split('\n');

    let Some(header) = lines.next() else {
        anyhow::bail!("invalid empty response")
    };
    anyhow::ensure!(
        header == SOURCE_LIST_HEADER,
        "sources.txt is not a schema list"
    );

    Ok(lines
        .filter(|line| !line.is_empty() && !line.starts_with('#'))
        .map(str::to_string)
        .collect())
}

#[derive(Debug, Clone, Deserialize, PartialEq, Eq, PartialOrd, Ord)]
pub struct KindId(pub String);

#[derive(Debug, Deserialize)]
#[serde(rename_all = "camelCase")]
pub struct KindDef {
    #[serde(default)]
    pub help: String,
    #[serde(default)]
    pub can_display: bool,
    #[serde(default)]
    pub metadata: KnownKindMetadata,
}

#[derive(Debug, Default, Deserialize)]
pub struct KnownKindMetadata {
    #[serde(default, rename = "infoapi/is-root")]
    pub is_root: bool,
    #[serde(default, rename = "infoapi:browser/template-name")]
    pub template_name: Option<String>,
    #[serde(default, rename = "infoapi/source-plugin")]
    pub source_plugin: Option<String>,
}

#[derive(Debug, Clone, Deserialize, PartialEq, Eq, PartialOrd, Ord)]
pub struct MappingName(pub String);
impl MappingName {
    pub fn last(&self) -> &str {
        self.0.split(':').next_back().expect("split is nonempty")
    }

    pub fn minify<'t>(&'t self, others: impl Iterator<Item = &'t MappingName>) -> String {
        let my_last = self.last();
        let mut collisions: Vec<Vec<_>> = others
            .filter(|other| other.0 != self.0 && other.last() == my_last)
            .map(|other| other.0.split(':').collect())
            .collect();

        let mut my_pieces: Vec<_> = self.0.split(':').collect();
        _ = my_pieces.pop();
        let mut use_prefixes = 0;

        fn has_subprefix(haystack: &[&str], needle: &[&str]) -> bool {
            let mut needle = needle.iter().copied().peekable();
            for &haystack_part in haystack {
                let Some(&needle_part) = needle.peek() else {
                    // needle is fully removed
                    return true;
                };
                if haystack_part == needle_part {
                    _ = needle.next();
                }
            }

            needle.next().is_none()
        }

        while !collisions.is_empty() {
            use_prefixes += 1;
            if use_prefixes > my_pieces.len() {
                // subset of another, cannot fully qualify
                break;
            }

            collisions.retain(|other| {
                has_subprefix(&other[..other.len() - 1], &my_pieces[..use_prefixes])
            })
        }

        let mut out = my_pieces;
        out.truncate(use_prefixes);
        out.push(my_last);
        out.join(":")
    }
}

#[derive(Debug, Deserialize)]
#[serde(rename_all = "camelCase")]
pub struct MappingDef {
    pub source_kind: KindId,
    pub target_kind: KindId,
    pub name: MappingName,
    pub is_implicit: bool,
    pub parameters: Vec<ParamDef>,
    pub mutable: bool,
    pub help: String,
    #[serde(default)]
    pub metadata: KnownMappingMetadata,
}

#[derive(Debug, Default, Deserialize)]
pub struct KnownMappingMetadata {
    #[serde(default, rename = "infoapi/source-plugin")]
    pub source_plugin: Option<String>,
    #[serde(default, rename = "infoapi/alias-of")]
    pub alias_of: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct ParamName(pub String);

#[derive(Debug, Deserialize)]
#[serde(rename_all = "camelCase")]
pub struct ParamDef {
    pub name: ParamName,
    pub kind: KindId,
    pub multi: bool,
    pub optional: bool,
}

#[derive(Deserialize)]
pub struct SourceSchema {
    kinds: BTreeMap<KindId, KindDef>,
    mappings: Vec<MappingDef>,
}

#[derive(Clone)]
pub struct Data(Rc<All>);

impl PartialEq for Data {
    fn eq(&self, other: &Self) -> bool {
        Rc::ptr_eq(&self.0, &other.0)
    }
}

impl ops::Deref for Data {
    type Target = All;

    fn deref(&self) -> &Self::Target {
        &self.0
    }
}

#[derive(Default)]
pub struct All {
    pub kinds: BTreeMap<KindId, KindDef>,
    pub mappings: BTreeMap<KindId, BTreeMap<MappingName, Rc<MappingDef>>>,
    pub known_plugins: BTreeSet<String>,
    pub errors: Vec<anyhow::Error>,
}

impl Extend<anyhow::Result<SourceSchema>> for All {
    fn extend<T: IntoIterator<Item = anyhow::Result<SourceSchema>>>(&mut self, iter: T) {
        for schema in iter {
            match schema {
                Ok(schema) => {
                    self.kinds.extend(schema.kinds);
                    for mapping in schema.mappings {
                        if let Some(plugin) = &mapping.metadata.source_plugin {
                            self.known_plugins.insert(plugin.clone());
                        }

                        self.mappings
                            .entry(mapping.source_kind.clone())
                            .or_default()
                            .insert(mapping.name.clone(), Rc::new(mapping));
                    }
                }
                Err(err) => self.errors.push(err),
            }
        }
    }
}

async fn fetch_source(source_url: &str) -> anyhow::Result<SourceSchema> {
    http::Request::get(source_url)
        .send()
        .await
        .context("HTTP")?
        .json::<SourceSchema>()
        .await
        .context("parse results as JSON")
}

pub async fn all() -> anyhow::Result<Data> {
    let sources = fetch_sources().await.context("fetch sources list")?;

    let futures: FuturesUnordered<_> = sources
        .into_iter()
        .map(|source| async move {
            fetch_source(&source)
                .await
                .with_context(|| format!("fetch source from {source}"))
        })
        .collect();
    let schema: All = futures.collect().await;
    anyhow::ensure!(!schema.kinds.is_empty(), "all sources cannot be loaded");
    Ok(Data(Rc::new(schema)))
}
