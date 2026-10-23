const menuButton = document.querySelector(".menu-toggle");
const siteNavigation = document.querySelector("#site-nav");

if (menuButton && siteNavigation) {
    menuButton.addEventListener("click", () => {
        const isOpen = menuButton.getAttribute("aria-expanded") === "true";
        menuButton.setAttribute("aria-expanded", String(!isOpen));
        siteNavigation.classList.toggle("is-open", !isOpen);
    });

    siteNavigation.addEventListener("click", (event) => {
        if (event.target instanceof HTMLAnchorElement) {
            menuButton.setAttribute("aria-expanded", "false");
            siteNavigation.classList.remove("is-open");
        }
    });
}

const yearElement = document.querySelector("[data-current-year]");
if (yearElement) {
    yearElement.textContent = String(new Date().getFullYear());
}
