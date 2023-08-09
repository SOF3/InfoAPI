use std::{
    borrow,
    cell::RefCell,
    collections::{hash_map, HashMap},
    hash::Hash,
    ops,
    rc::Rc,
};

use defy::defy;
use yew::{prelude::*, suspense};

mod components;
mod data;
mod util;

#[function_component]
pub fn App() -> Html {
    defy! {
        Suspense(fallback = fallback()) {
            Main;
        }
    }
}

#[function_component]
fn Main() -> HtmlResult {
    let schema = suspense::use_future(data::all)?;
    let schema = match &*schema {
        Ok(schema) => schema,
        Err(err) => {
            return Ok(defy! {
                div(class = "section") {
                    div(class = "container") {
                        article(class = "message is-danger") {
                            div(class = "message-header") {
                                + "Error";
                            }

                            div(class = "message-body") {
                                pre {
                                    + format!("{err:?}");
                                }
                            }
                        }
                    }
                }
            })
        }
    };

    let plugin_filter = use_state(|| {
        schema
            .known_plugins
            .iter()
            .cloned()
            .collect::<PluginFilter>()
    });
    let toggle_plugin = |plugin: &str| {
        let plugin_filter = plugin_filter.clone();
        let plugin = plugin.to_string();
        Callback::from(move |()| {
            let mut plugin_filter_map = (*plugin_filter).clone();
            plugin_filter_map.toggle(plugin.clone());
            plugin_filter.set(plugin_filter_map);
        })
    };

    let source_kind = use_state(|| {
        let (kind, _) = schema
            .kinds
            .iter()
            .find(|(_, def)| def.metadata.is_root)
            .expect("no kinds");
        kind.clone()
    });

    Ok(defy! {
        if !schema.errors.is_empty() {
            div(class = "fixed-corner is-pulled-right mx-3 my-3") {
                components::Modal(button = defy! {
                    button(class = "button is-borderless") {
                        span(class = "icon has-text-danger is-large") {
                            i(class = "mdi mdi-48px mdi-alert-circle");
                        }
                    }
                }) {
                    article(class = "message is-danger") {
                        div(class = "message-header") {
                            + "Error fetching schema for some plugins:";
                        }

                        for err in &schema.errors {
                            div(class = "message-body") {
                                pre {
                                    + format!("{err:?}");
                                }
                            }
                        }
                    }
                }
            }
        }

        div(class = "section") {
            div(class = "container") {
                h1(class = "title") {
                    + "InfoAPI template editor";
                }

                div(class = "field") {
                }

                div(class = "field") {
                    label(class = "label is-medium") {
                        + "Which template are you editing?";
                    }

                    div(class = "level") {
                        components::EditableSelect<data::KindId>(
                            options = schema.kinds.iter().filter_map(|(k, v)| {
                                let k = k.clone();

                                if !v.metadata.is_root {
                                    return None;
                                }

                                if let Some(template) = &v.metadata.template_name {
                                    return Some((k, template.clone()));
                                }

                                Some((k, v.help.clone()))
                            }).collect::<Vec<(data::KindId, String)>>(),
                            on_change = Callback::from({
                                let source_kind = source_kind.clone();
                                move |kind| source_kind.set(kind)
                            }),
                            button_class = "is-medium",
                            input_class = "is-medium",
                        );

                        components::Modal(button = defy! {
                            button(class = "button is-info is-small") {
                                span(class = "icon") {
                                    i(class = "mdi mdi-wrench");
                                }
                                span { + "Select plugins"; }
                            }
                        }) {
                            div(class = "box panel") {
                                p(class = "panel-heading") {
                                    + "Select plugins";
                                }
                                div(class = "panel-block") {
                                    p(class = "is-size-7") {
                                        + "Uncheck plugins here to hide them from search results.";
                                    }
                                }

                                for plugin in &schema.known_plugins {
                                    a(class = "panel-block", onclick = toggle_plugin(plugin).reform(|_| ())) {
                                        span(class = "icon") {
                                            if plugin_filter.contains(Some(plugin)) {
                                                i(class = "mdi mdi-check");
                                            } else {
                                                i(class = "mdi mdi-cancel");
                                            }
                                        }
                                        + plugin.clone();
                                    }
                                }
                            }
                        }
                    }
                }

                div(class = "field") {
                    components::Expression(
                        schema = schema.clone(),
                        source_kind = (*source_kind).clone(),
                        plugins = (*plugin_filter).clone(),
                    );
                }
            }
        }
    })
}

#[derive(Debug, Clone)]
pub struct PluginFilter(Rc<RefCell<HashMap<String, ()>>>);
impl PartialEq for PluginFilter {
    fn eq(&self, other: &Self) -> bool {
        Rc::ptr_eq(&self.0, &other.0)
    }
}
impl FromIterator<String> for PluginFilter {
    fn from_iter<T: IntoIterator<Item = String>>(iter: T) -> Self {
        let map = iter.into_iter().map(|k| (k, ())).collect();
        Self(Rc::new(RefCell::new(map)))
    }
}
impl PluginFilter {
    fn contains<Q: Hash + Eq>(&self, source_plugin: Option<impl ops::Deref<Target = Q>>) -> bool
    where
        String: borrow::Borrow<Q>,
    {
        match &source_plugin {
            Some(plugin) => self.0.borrow().contains_key(plugin),
            None => true,
        }
    }

    fn toggle(&mut self, plugin: String) {
        let mut map = self.0.borrow_mut();
        match map.entry(plugin) {
            hash_map::Entry::Occupied(entry) => entry.remove(),
            hash_map::Entry::Vacant(entry) => {
                entry.insert(());
            }
        }
    }
}

fn fallback() -> Html {
    defy! {
        section(class = "hero is-fullheight") {
            div(class = "hero-body") {
                p(class = "title has-text") {
                    + "Loading\u{2026}";
                }
            }
        }
    }
}
