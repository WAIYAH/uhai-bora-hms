document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const showPassword = input.type === "password";
        input.type = showPassword ? "text" : "password";
        button.textContent = showPassword ? "Hide" : "Show";
        button.setAttribute("aria-label", `${showPassword ? "Hide" : "Show"} password`);
    });
});

const password = document.querySelector("#newpassword");
const confirmation = document.querySelector("#cpassword");

if (password instanceof HTMLInputElement && confirmation instanceof HTMLInputElement) {
    const checkPasswords = () => {
        confirmation.setCustomValidity(
            confirmation.value !== "" && confirmation.value !== password.value
                ? "Passwords do not match."
                : ""
        );
    };

    password.addEventListener("input", checkPasswords);
    confirmation.addEventListener("input", checkPasswords);
}
