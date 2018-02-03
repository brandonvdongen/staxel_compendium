class Recipe {
    constructor(item, name, ingredients, result) {
        this.item = item;
        this.key = name;
        this.name = get_name(name);
        this.ingredients = JSON.parse(ingredients);
        this.result = JSON.parse(result);

        this.div = this.create_div();
    }

    create_div() {
        if (this.div) {
            if (this.div.parentNode) {
                this.div.parentNode.removeChild(this.div);
            }
        }

        const item = document.createElement("div");
        item.classList.add("recipe");

        const name = document.createElement("div");
        name.classList.add("name");
        name.innerText = this.name;
        name.title = this.item.description;

        item.dataset.key = this.key;
        item.appendChild(name);


        item.onclick = (ev) => {
            if (selected_item) selected_item.div.classList.remove("selected");
            selected_item = this;
            selected_item.div.classList.add("selected");
        };

        return item;
    }
    updateLang(){
        this.name=get_name(this.key);
        this.div.getElementsByClassName("name")[0].innnerText = this.name;
    }
}