use defy::defy;
use yew::prelude::*;

#[function_component]
pub fn Modal(props: &Props) -> Html {
    let is_open = use_state(|| false);
    let button_cb = |open| {
        Callback::from({
            let is_open = is_open.clone();
            move |_| is_open.set(open)
        })
    };

    defy! {
        div(onclick = button_cb(true)) {
            +props.button.clone();
        }

        if *is_open {
            div(class = "modal is-active") {
                div(class = "modal-background", onclick = button_cb(false));
                div(class = "modal-content") {
                    + props.children.clone();
                }
                button(class = "modal-close is-large", aria-label = "close", onclick = button_cb(false));
            }
        }
    }
}

#[derive(PartialEq, Properties)]
pub struct Props {
    pub button: Html,
    pub children: Children,
}
