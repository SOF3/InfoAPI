use yew::{Callback, UseStateHandle};

pub fn state_callback<InputT, T: Clone + 'static>(
    handle: &UseStateHandle<T>,
    value: T,
) -> Callback<InputT, ()> {
    let handle = handle.clone();
    Callback::from(move |_| handle.set(value.clone()))
}

pub fn set_state<T: Clone + 'static>(handle: &UseStateHandle<T>) -> Callback<T, ()> {
    let handle = handle.clone();
    Callback::from(move |value| handle.set(value))
}
