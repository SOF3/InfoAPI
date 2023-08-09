use std::rc::Rc;

use defy::defy;
use yew::prelude::*;
use yew_hooks::use_clipboard;

use crate::{
    data::{Data, KindId, MappingDef},
    util::set_state,
    PluginFilter,
};

#[function_component]
pub fn Expression(props: &Props) -> Html {
    let path = use_state(Vec::<Step>::new);
    let terminal_kind = match path.last() {
        Some(step) => &step.mapping.target_kind,
        None => &props.source_kind,
    };

    let selected_mapping = use_state(|| None::<Step>);

    let push_mapping = Callback::from({
        let path = path.clone();
        let selected_mapping = selected_mapping.clone();

        move |step| {
            let mut path_vec = (*path).clone();
            path_vec.push(step);
            path.set(path_vec);
            selected_mapping.set(None);
        }
    });

    let truncate_steps = Callback::from({
        let path = path.clone();
        let selected_mapping = selected_mapping.clone();

        move |i: Option<usize>| {
            let mut path_vec = (*path).clone();
            path_vec.truncate(i.map_or(0, |i| i + 1));
            path.set(path_vec);
            selected_mapping.set(None);
        }
    });

    let step_strings: Vec<_> = path
        .iter()
        .map(|step| step.minified_name.as_str())
        .collect();
    let template_string = format!("{{{}}}", step_strings.join(" "));

    let clipboard = use_clipboard();

    defy! {
        div(class = "level") {
            label(class = "label is-medium") {
                + "Build your expression";
            }

            div(class = "level") {
                input(
                    class = "input",
                    type = "text", readonly = true,
                    value = template_string.clone(),
                );

                button(class = "button", onclick = Callback::from({
                    let clipboard = clipboard.clone();
                    let template_string = template_string.clone();
                    move |_| clipboard.write_text(template_string.clone())
                })) {
                    span(class = "icon") {
                        i(class = "mdi mdi-content-copy");
                    }
                    span {
                        + "Copy";
                    }
                }
            }
        }

        div(class = "box") {
            nav(class = "breadcrumb", aria-label = "breadcrumbs") {
                ul {
                    StepButton(
                        name = "", icon = Some(classes!["mdi-play"]),
                        reset = truncate_steps.reform(|_| None),
                    );

                    for (i, step) in path.iter().enumerate() {
                        StepButton(
                            name = step.minified_name.clone(),
                            reset = truncate_steps.reform(move |_| Some(i)),
                        );
                    }
                }
            }
        }

        div(class = "box") {
            h2(class = "heading") { + "Drill down"; }

            div(class = "columns") {
                div(class = "column") {
                    MappingList(
                        kind = terminal_kind.clone(),
                        plugins = props.plugins.clone(),
                        schema = props.schema.clone(),
                        choose_mapping = set_state(&selected_mapping).reform(Some),
                    );
                }

                div(class = "column") {
                    if let Some(mapping) = &*selected_mapping {
                        article {
                            div(class = "message-header") {
                                + mapping.mapping.name.0.clone();
                            }
                            div(class = "message-body") {
                                p { + &mapping.mapping.help; }
                                button(class = "button is-primary", onclick = push_mapping.reform({
                                    let mapping = mapping.clone();
                                    move |_| mapping.clone()
                                })) {
                                    span(class = "icon") {
                                        i(class = "mdi mdi-plus");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

#[derive(PartialEq, Properties)]
pub struct Props {
    pub source_kind: KindId,
    pub schema: Data,
    pub plugins: PluginFilter,
}

#[function_component]
fn StepButton(props: &StepProps) -> Html {
    defy! {
        li {
            button(class = classes!["button", "is-link"], onclick = props.reset.reform(|_| ())) {
                if let Some(icon) = &props.icon {
                    span(class = "icon") {
                        i(class = classes!["mdi", icon.clone()]);
                    }
                }

                if !props.name.is_empty() {
                    span {
                        + &props.name;
                    }
                }
            }
        }
    }
}

#[derive(PartialEq, Properties)]
struct StepProps {
    #[prop_or_default]
    name: String,
    #[prop_or_default]
    icon: Option<Classes>,
    reset: Callback<()>,
}

#[function_component]
fn MappingList(props: &MappingListProps) -> Html {
    let Some(mappings) = props.schema.mappings.get(&props.kind) else {
        return defy! {
            span {
                + "No mappings";
            }
        };
    };

    defy! {
        for mapping in mappings.values() {
            if mapping.metadata.alias_of.is_none() && props.plugins.contains(mapping.metadata.source_plugin.as_ref()) {
                let minified_name = mapping.name.minify(mappings.keys());

                button(class = "button", onclick = props.choose_mapping.reform({
                    let mapping = mapping.clone();
                    move |_| Step {
                        mapping: mapping.clone(),
                        minified_name: minified_name.clone(),
                    }
                })) {
                    + mapping.name.last();
                }
            }
        }
    }
}

#[derive(PartialEq, Properties)]
struct MappingListProps {
    kind: KindId,
    plugins: PluginFilter,
    schema: Data,
    choose_mapping: Callback<Step>,
}

#[derive(Clone)]
struct Step {
    mapping: Rc<MappingDef>,
    minified_name: String,
}
