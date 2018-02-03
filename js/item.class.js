class Item {
    constructor(name, description, type, categories, buy, sell) {
        this.key = name;
        this.name = get_name(name);
        this.description = get_desc(description);
        this.type = type;
        this.categories = JSON.parse(categories);
        if (buy < 0) {
            this.buy = "Not buyable";
        }
        else {
            this.buy = buy;
        }
        if (sell < 0) {
            this.sell = "Not sellable";
        }
        else {
            this.sell = sell;
        }

        this.div = this.create_div();
    }

    updateLang() {
        const new_name = get_name(this.key);
        if (new_name !== undefined && new_name.length > 1) this.name = new_name;
        else this.name = this.name+ " (no translation)";
        this.description = get_desc(this.key);
        this.div.querySelector(".name").innerHTML = this.name;
        this.div.querySelector(".name").title = this.description;

    }

    create_div() {
        if (this.div) {
            if (this.div.parentNode) {
                this.div.parentNode.removeChild(this.div);
            }
        }

        const item = document.createElement("div");
        item.classList.add("item");

        const name = document.createElement("div");
        name.classList.add("name");
        name.innerText = this.name;
        name.title = this.description;

        // const type = document.createElement("div");
        // type.classList.add("type");
        // type.innerText = this.type;

        const buy = document.createElement("div");
        buy.classList.add("buy");
        buy.innerText = this.buy;
        const buy_icon = document.createElement("img");
        buy_icon.src = "images/BuyPetalSymbol.png";
        buy_icon.classList.add("petal-icon");

        const sell = document.createElement("div");
        sell.classList.add("sell");
        sell.innerText = this.sell;
        const sell_icon = document.createElement("img");
        sell_icon.src = "images/SellPetalSymbol.png";
        sell_icon.classList.add("petal-icon");

        item.appendChild(name);

        // item.appendChild(type);

        buy.appendChild(buy_icon);
        item.appendChild(buy);
        item.dataset.key = this.key;
        sell.appendChild(sell_icon);
        item.appendChild(sell);
        item.onclick = (ev) => {
            if (selected_item) selected_item.div.classList.remove("selected");
            selected_item = this;
            selected_item.div.classList.add("selected");
        };

        return item;
    }
}