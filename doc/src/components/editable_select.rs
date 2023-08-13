use defy::defy;
use yew::prelude::*;

use crate::util::state_callback;

#[function_component]
pub fn EditableSelect<K: Clone + PartialEq + 'static>(props: &Props<K>) -> Html {
    let is_open = use_state(|| false);
    let input_focused = use_state(|| false);

    let selection = use_state(|| props.default);
    let set_select = Callback::from({
        let selection = selection.clone();
        let is_open = is_open.clone();
        let key_cb = props.on_change.clone();
        let options = props.options.clone();
        move |i| {
            selection.set(i);
            is_open.set(false);
            key_cb.emit(options.get(i).expect("invalid selection").0.clone());
        }
    });

    let user_input = use_state(String::new);
    let input_node_ref = use_node_ref();
    let update_user_input = {
        let user_input = user_input.clone();
        let input_node_ref = input_node_ref.clone();
        Callback::from(move |()| {
            user_input.set(
                input_node_ref
                    .cast::<web_sys::HtmlInputElement>()
                    .unwrap()
                    .value(),
            )
        })
    };

    let selected_value = props
        .options
        .get(*selection)
        .map_or_else(String::new, |(_, s)| s.clone());

    defy! {
        div(class = classes!["dropdown", is_open.then(|| "is-active")]) {
            div(class = "dropdown-trigger") {
                button(
                    class = classes![props.button_class.clone(), "button"],
                    aria-haspopup = "true",
                    aria-controls = "dropdown-menu",
                    onfocusin = state_callback(&is_open, true),
                    onfocusout = state_callback(&is_open, false),
                ) {
                    input(
                        class = classes![props.input_class.clone(), "input", "is-borderless"],
                        value = if *input_focused { user_input.to_string() } else { selected_value.clone() },
                        placeholder = selected_value.clone(),
                        ref = input_node_ref.clone(),
                        onfocus = state_callback(&input_focused, true),
                        onblur = state_callback(&input_focused, false),
                        oninput = update_user_input.reform(|_| ()),
                        onchange = update_user_input.reform(|_| ()),
                    );
                }
            }

            div(class = "dropdown-menu", role = "menu") {
                div(class = "dropdown-content") {
                    for (i, (_, option)) in props.options.iter().enumerate() {
                        if option.to_lowercase().contains(user_input.to_lowercase().as_str()) {
                            a(href = "#", class = "dropdown-item", onclick = set_select.reform(move |_| i)) {
                                + option;
                            }
                        }
                    }
                }
            }
        }
    }
}

#[derive(PartialEq, Properties)]
pub struct Props<K: Clone + PartialEq> {
    pub options: Vec<(K, String)>,
    pub on_change: Callback<K>,

    #[prop_or_default]
    pub default: usize,
    #[prop_or_default]
    pub button_class: Classes,
    #[prop_or_default]
    pub input_class: Classes,
}
