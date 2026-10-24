/*
 * Uhai Bora progressive enhancements. Every page works without this file:
 * menus are visible lists, forms submit normally and confirmations use <details>.
 * Written in ES2017-compatible syntax so a parse error on older browsers cannot
 * take navigation down with it.
 */
(function () {
    "use strict";

    var root = document.documentElement;
    root.classList.add("js");

    function onReady(callback) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", callback);
        } else {
            callback();
        }
    }

    function focusFirst(container) {
        var target = container.querySelector("a[href], button:not([disabled]), input:not([type='hidden']), select, textarea");
        if (target) {
            target.focus();
        }
    }

    /* Account menus: a button that shows and hides a list of links. */
    function setupDisclosures() {
        var disclosures = [];

        Array.prototype.forEach.call(document.querySelectorAll("[data-disclosure]"), function (wrapper) {
            var toggle = wrapper.querySelector("[data-disclosure-toggle]");
            var panel = wrapper.querySelector("[data-disclosure-panel]");
            if (!toggle || !panel) {
                return;
            }

            function setOpen(open, restoreFocus) {
                toggle.setAttribute("aria-expanded", open ? "true" : "false");
                panel.hidden = !open;
                if (!open && restoreFocus) {
                    toggle.focus();
                }
            }

            var item = { wrapper: wrapper, close: function (restoreFocus) { setOpen(false, restoreFocus); } };
            disclosures.push(item);
            setOpen(false, false);

            toggle.addEventListener("click", function () {
                var willOpen = toggle.getAttribute("aria-expanded") !== "true";
                disclosures.forEach(function (other) {
                    if (other !== item) {
                        other.close(false);
                    }
                });
                setOpen(willOpen, false);
            });

            wrapper.addEventListener("keydown", function (event) {
                if (event.key === "Escape" && toggle.getAttribute("aria-expanded") === "true") {
                    event.stopPropagation();
                    setOpen(false, true);
                }
            });

            wrapper.addEventListener("focusout", function (event) {
                if (event.relatedTarget && !wrapper.contains(event.relatedTarget)) {
                    setOpen(false, false);
                }
            });
        });

        document.addEventListener("pointerdown", function (event) {
            disclosures.forEach(function (item) {
                if (!item.wrapper.contains(event.target)) {
                    item.close(false);
                }
            });
        });

        return disclosures;
    }

    /* Mobile main navigation. */
    function setupMobileNav(disclosures) {
        var toggle = document.querySelector("[data-nav-toggle]");
        var panel = document.querySelector("[data-nav-panel]");
        if (!toggle || !panel) {
            return;
        }
        var desktop = window.matchMedia("(min-width: 64em)");

        function setOpen(open, restoreFocus) {
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
            panel.classList.toggle("is-open", open);
            if (open) {
                disclosures.forEach(function (item) { item.close(false); });
                focusFirst(panel);
            } else if (restoreFocus) {
                toggle.focus();
            }
        }

        toggle.addEventListener("click", function () {
            setOpen(toggle.getAttribute("aria-expanded") !== "true", false);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && toggle.getAttribute("aria-expanded") === "true") {
                setOpen(false, true);
            }
        });

        panel.addEventListener("click", function (event) {
            if (event.target.closest && event.target.closest("a")) {
                setOpen(false, false);
            }
        });

        document.addEventListener("pointerdown", function (event) {
            if (toggle.getAttribute("aria-expanded") === "true" && !panel.contains(event.target) && !toggle.contains(event.target)) {
                setOpen(false, false);
            }
        });

        var reset = function () {
            if (desktop.matches) {
                setOpen(false, false);
            }
        };
        if (desktop.addEventListener) {
            desktop.addEventListener("change", reset);
        } else if (desktop.addListener) {
            desktop.addListener(reset);
        }
    }

    /* Show / hide password buttons. */
    function setupPasswordToggles() {
        Array.prototype.forEach.call(document.querySelectorAll("[data-password-toggle]"), function (button) {
            var input = document.getElementById(button.getAttribute("data-password-toggle"));
            var label = button.querySelector("[data-label]");
            if (!input) {
                return;
            }
            button.addEventListener("click", function () {
                var show = input.type === "password";
                input.type = show ? "text" : "password";
                button.setAttribute("aria-pressed", show ? "true" : "false");
                if (label) {
                    label.textContent = show ? "Hide" : "Show";
                }
            });
        });
    }

    /* Live check that the confirmation matches the new password (the server checks again). */
    function setupPasswordMatch() {
        Array.prototype.forEach.call(document.querySelectorAll("[data-password-confirm]"), function (confirmation) {
            var password = document.getElementById(confirmation.getAttribute("data-password-confirm"));
            if (!password) {
                return;
            }
            var check = function () {
                confirmation.setCustomValidity(
                    confirmation.value !== "" && confirmation.value !== password.value ? "The passwords do not match." : ""
                );
            };
            password.addEventListener("input", check);
            confirmation.addEventListener("input", check);
        });
    }

    /* Registration: show guardian details only when the date of birth is under 18 (the server enforces this too). */
    function setupGuardianSection() {
        var dob = document.querySelector("[data-dob]");
        var section = document.querySelector("[data-guardian]");
        if (!dob || !section) {
            return;
        }
        var inputs = section.querySelectorAll("input");
        var update = function () {
            var minor = false;
            if (/^\d{4}-\d{2}-\d{2}$/.test(dob.value)) {
                var parts = dob.value.split("-");
                var birth = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                var adult = new Date(birth.getFullYear() + 18, birth.getMonth(), birth.getDate());
                minor = adult > new Date();
            }
            section.hidden = !minor && section.getAttribute("data-guardian-error") !== "true";
            Array.prototype.forEach.call(inputs, function (input) {
                input.required = minor;
            });
        };
        dob.addEventListener("change", update);
        dob.addEventListener("input", update);
        update();
    }

    /* Instant filtering of specialty cards (the search form still works without JavaScript). */
    function setupCardFilter() {
        var input = document.querySelector("[data-filter-input]");
        var list = document.querySelector("[data-filter-list]");
        var status = document.querySelector("[data-filter-status]");
        if (!input || !list) {
            return;
        }
        var items = list.querySelectorAll("[data-filter-text]");
        input.addEventListener("input", function () {
            var query = input.value.trim().toLowerCase();
            var shown = 0;
            Array.prototype.forEach.call(items, function (item) {
                var match = query === "" || item.getAttribute("data-filter-text").indexOf(query) !== -1;
                item.hidden = !match;
                if (match) {
                    shown += 1;
                }
            });
            if (status) {
                status.textContent = query === ""
                    ? ""
                    : (shown === 0 ? "No specialties match “" + input.value.trim() + "”." : shown + (shown === 1 ? " specialty matches." : " specialties match."));
            }
        });
    }

    /* Prevent double submission (for example a double tap on "Confirm booking"). */
    function setupSubmitOnce() {
        Array.prototype.forEach.call(document.querySelectorAll("form[data-submit-once]"), function (form) {
            form.addEventListener("submit", function () {
                var button = form.querySelector("button[type='submit']");
                if (!button) {
                    return;
                }
                window.setTimeout(function () {
                    button.disabled = true;
                    button.setAttribute("aria-disabled", "true");
                    var loading = button.getAttribute("data-loading-label");
                    if (loading) {
                        button.textContent = loading;
                    }
                }, 0);
            });
        });
    }

    /* Move focus to the error summary so screen reader and keyboard users hear it first. */
    function focusErrorSummary() {
        var summary = document.querySelector("[data-error-summary]");
        if (summary) {
            summary.focus();
        }
    }

    /* When a destructive confirmation opens, move focus into it. */
    function setupConfirmations() {
        Array.prototype.forEach.call(document.querySelectorAll("details[data-confirm]"), function (details) {
            details.addEventListener("toggle", function () {
                if (details.open) {
                    var button = details.querySelector(".confirm__panel button, .confirm__panel input:not([type='hidden'])");
                    if (button) {
                        button.focus();
                    }
                }
            });
        });
    }

    onReady(function () {
        var disclosures = setupDisclosures();
        setupMobileNav(disclosures);
        setupPasswordToggles();
        setupPasswordMatch();
        setupGuardianSection();
        setupCardFilter();
        setupSubmitOnce();
        setupConfirmations();
        focusErrorSummary();
    });
})();
