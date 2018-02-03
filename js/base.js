'use strict';

let known_items = new Map();
let language = new Map();
let known_recipes = new Map();
let selected_item;

function set_localization(lang) {
    language = new Map();
    getLanguage(lang,()=>{
        known_items.forEach((value, key, map) => {
            value.updateLang();
        });
        known_recipes.forEach((value, key, map) => {
            value.updateLang();
        });
    });

}

function get_name(name) {
    let new_name;
    new_name = language.get(name);
    if (new_name !== undefined) {
        return new_name;
    }
    new_name = language.get(name + ".name");
    if (new_name !== undefined) {
        return new_name;
    }
    return false;
}

function get_desc(desc) {
    let new_desc = language.get(desc + ".description");
    if (new_desc !== undefined && new_desc.trim()) {

        return new_desc;
    } else {
        return "no description available";
    }
}

const clearEntries = (elem) => {
    return new Promise((resolve, reject) => {
        const entries = elem.querySelectorAll(".item");
        entries.forEach(value => {
            if (value instanceof HTMLElement) value.parentNode.removeChild(value);
            else reject(Error("Something went wrong, FUCK!"));

        });
        resolve(true);
    })
};

function getItems(callback) {
    Post("php/control.php", "all_items", (post) => {
        const items = JSON.parse(post.response)[0];
        if (items.length > 0) {
            items.forEach((value, index, array) => {
                const new_name = get_name(value.name);
                let entry;
                if (new_name) {
                    entry = new Item(value.name, value.name, value.type, value.categories, value.buy, value.sell);
                    known_items.set(value.name, entry);
                }
                delete array[index];
            });
        }
        const recipes = JSON.parse(post.response)[1];
        if (recipes.length > 0) {
            recipes.forEach((value, index, array) => {
                const item = known_items.get(value.name);
                const entry = new Recipe(item, value.name, value.ingredients, value.result);
                known_recipes.set(value.name, entry);
                delete array[index];
            });
        }
        if (typeof callback === "function") {
            callback();
        }
    });
}

function getLanguage(lang, callback) {
    Post("php/control.php", "language=" + lang, (post) => {
        const items = JSON.parse(post.response);
        if (items.length > 0) {
            items.forEach((value, index, array) => {
                language.set(value.code, value.name);
            });
        }
        if (typeof callback === "function") {
            callback();
        }
    });
}


document.addEventListener("DOMContentLoaded", () => {
    const list_container = document.getElementById("list_container");
    const crafting_container = document.getElementById("crafting_container");
    const item_list = document.getElementById("item_list");
    const crafting_list = document.getElementById("crafting_list");
    const search = document.getElementById("search");
    const crafting_search = document.getElementById("crafting_search");
    const notification = document.getElementById("notification_container");
    const notification_close = notification.getElementsByClassName("button")[0];
    notification_close.addEventListener("click", (ev) => {
        notification.parentElement.removeChild(notification);
    });

    getLanguage("en-GB", () => {
        clearEntries(item_list);
        clearEntries(crafting_list);
        getItems(() => {
            known_items.forEach((value, key, map) => {
                if (value.div instanceof HTMLElement) {
                    setTimeout(() => {
                        item_list.appendChild(value.div);
                    }, 1);
                }
            });

            known_recipes.forEach((value, key, map) => {
                if (value.div instanceof HTMLElement) {
                    setTimeout(() => {
                        crafting_list.appendChild(value.div);
                    }, 1);
                }
            });

            const buytab = document.getElementById("buytab");
            const craftingtab = document.getElementById("craftingtab");

            buytab.onclick = (ev) => {
                crafting_container.classList.add("hidden");
                list_container.classList.remove("hidden");
            };
            craftingtab.onclick = (ev) => {
                list_container.classList.add("hidden");
                crafting_container.classList.remove("hidden");
            };

            search.addEventListener("keyup", (ev) => {
                if (ev.key === "Enter") {
                    if (search.value.length !== 0) {
                        const regex = RegExp("([\\W]|^)" + search.value + ".*", "gi");
                        known_items.forEach((value, index, array) => {
                            if ((value.categories.indexOf(search.value) > 0) || regex.test(value.name)) {
                                value.div.classList.remove("hide");
                            } else {
                                value.div.classList.add("hide");
                            }
                        });
                    } else {
                        known_items.forEach((value, index, array) => {
                            value.div.classList.remove("hide");
                        });
                    }

                }
            });
            crafting_search.addEventListener("keyup", (ev) => {
                if (ev.key === "Enter") {
                    if (crafting_search.value.length !== 0) {
                        const regex = RegExp("([\\W]|^)" + search.value + ".*", "gi");
                        known_recipes.forEach((value, index, array) => {
                            if (regex.test(value.name)) {
                                value.div.classList.remove("hide");
                            } else {
                                value.div.classList.add("hide");
                            }
                        });
                    } else {
                        known_recipes.forEach((value, index, array) => {
                            value.div.classList.remove("hide");
                        });
                    }

                }
            });
        });

    });
});