const menuButton = document.querySelector(".menu-toggle");
const siteNavigation = document.querySelector("#site-nav");
const accountToggle = document.querySelector(".account-toggle");
const accountDropdown = document.querySelector(".account-dropdown");

if (accountToggle && accountDropdown) {
    const closeAccountMenu = (restoreFocus = false) => {
        accountToggle.setAttribute("aria-expanded", "false");
        accountDropdown.hidden = true;
        if (restoreFocus) {
            accountToggle.focus();
        }
    };

    accountToggle.addEventListener("click", () => {
        const isExpanded = accountToggle.getAttribute("aria-expanded") === "true";
        accountToggle.setAttribute("aria-expanded", String(!isExpanded));
        accountDropdown.hidden = isExpanded;
    });

    accountDropdown.addEventListener("click", (event) => {
        if (event.target instanceof Element && event.target.closest("a")) {
            closeAccountMenu();
        }
    });

    document.addEventListener("click", (event) => {
        if (event.target instanceof Node && !event.target.parentElement?.closest(".account-menu")) {
            closeAccountMenu();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && accountToggle.getAttribute("aria-expanded") === "true") {
            closeAccountMenu(true);
        }
    });
}

if (menuButton && siteNavigation) {
    menuButton.addEventListener("click", () => {
        const isOpen = menuButton.getAttribute("aria-expanded") === "true";
        menuButton.setAttribute("aria-expanded", String(!isOpen));
        siteNavigation.classList.toggle("is-open", !isOpen);
    });

    siteNavigation.addEventListener("click", (event) => {
        if (event.target instanceof Element && event.target.closest("a")) {
            menuButton.setAttribute("aria-expanded", "false");
            siteNavigation.classList.remove("is-open");
        }
    });
}

const yearElement = document.querySelector("[data-current-year]");
if (yearElement) {
    yearElement.textContent = String(new Date().getFullYear());
}

const shareUrl = new URL(window.location.pathname, window.location.origin).href;
const shareText = "Discover Uhai Bora - care that fits your life.";
const shareLinks = {
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`,
    whatsapp: `https://wa.me/?text=${encodeURIComponent(`${shareText} ${shareUrl}`)}`,
    x: `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareUrl)}`
};

document.querySelectorAll("[data-share]").forEach((link) => {
    const target = link.getAttribute("data-share");
    if (target && shareLinks[target]) {
        link.setAttribute("href", shareLinks[target]);
    }
});

const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
const videos = document.querySelectorAll("video[autoplay]");

if (reducedMotion.matches) {
    videos.forEach((video) => video.pause());
} else if ("IntersectionObserver" in window) {
    const videoObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const video = entry.target;
            if (video instanceof HTMLVideoElement && !entry.isIntersecting) {
                video.pause();
            } else if (video instanceof HTMLVideoElement && !reducedMotion.matches) {
                video.play().catch(() => {
                    video.removeAttribute("autoplay");
                });
            }
        });
    }, { threshold: 0.15 });

    videos.forEach((video) => videoObserver.observe(video));

    reducedMotion.addEventListener("change", (event) => {
        if (event.matches) {
            videos.forEach((video) => video.pause());
        }
    });
}

if (!reducedMotion.matches && "IntersectionObserver" in window) {
    const revealTargets = document.querySelectorAll(
        ".hero-copy, .story-copy, .story-art, .care-card, .cta-section"
    );
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    revealTargets.forEach((element) => {
        element.dataset.reveal = "pending";
        revealObserver.observe(element);
    });
}
