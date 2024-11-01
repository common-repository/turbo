//admin-side.js
var siteUrl = turbo_admin_side_script_vars.site_url;
var selectBillingCityName = turbo_admin_side_script_vars.selected_billing_city_name;
var selectBillingCityValue = turbo_admin_side_script_vars.selected_billing_city_value;
var selectShippingCityName = turbo_admin_side_script_vars.selected_shipping_city_name;
var selectShippingCityValue = turbo_admin_side_script_vars.selected_shipping_city_value;

var selectBillingStateValue = turbo_admin_side_script_vars.selected_billing_state_value;
var selectShippingStateValue = turbo_admin_side_script_vars.selected_shipping_state_value;

function fillCities(select, options) {
    for (var option in options) {
        if (options[option].status === 2) {
            select.appendChild(new Option(options[option].name + "   ( خارج التغطية )   ", options[option].id + ":" + options[option].name)).style.color = "#c5c5c5";
        } else {
            select.appendChild(new Option(options[option].name, options[option].id + ":" + options[option].name)); //array contain key = (id:name ) and value = (name)
        }
    }
}

function getAreas(dropdownElement, stateId, defaultValue) {
    if (!dropdownElement) {
        console.log("!dropdownElement");

        return;
    }
    if (!stateId) {
        dropdownElement.replaceChildren();
        return;
    }

    //reset select element
    dropdownElement.replaceChildren();

    // waiting message until the data arrives
    dropdownElement.appendChild(new Option("انتظر لحظات ....", "0"));

    fetch(siteUrl + "/wp-json/turbo/getareas?id=" + stateId)
        .then((response) => response.json())
        .then((data) => {
            dropdownElement.replaceChildren();
            fillCities(dropdownElement, data.feed);
            if (defaultValue) dropdownElement.value = defaultValue;
            console.log("Sucsess");
        })
        .catch((error) => {
            dropdownElement.replaceChildren();
            dropdownElement.appendChild(new Option("خطأ في الخوادم يرجى المحاولة لاحقاً", "0"));
            console.error("Error:", error);
        });
}

function getSelectedArea(dropdownElement, stateId, cityName, cityValue) {
    if (!dropdownElement) {
        console.log("!dropdownElement");

        return;
    }
    if (!stateId) {
        dropdownElement.replaceChildren();
        return;
    }

    //    dropdownElement.replaceChildren();

    // waiting message until the data arrives
    if (!cityName || !cityValue) {
        dropdownElement.replaceChildren();
        dropdownElement.appendChild(new Option("أعد اختيار محافظة لتحديد المدن", "0"));

        return;
    } else {
        fetch(siteUrl + "/wp-json/turbo/getareas?id=" + stateId)
            .then((response) => response.json())
            .then((data) => {
                dropdownElement.replaceChildren();
                // dropdownElement.appendChild(new Option(cityName, cityValue+':'+cityName));
                fillCities(dropdownElement, data.feed);
                if (cityValue) dropdownElement.value = cityValue + ":" + cityName;
                console.log("Sucsess");
            })
            .catch((error) => {
                dropdownElement.replaceChildren();
                dropdownElement.appendChild(new Option("خطأ في الخوادم يرجى المحاولة لاحقاً", "0"));
                console.error("Error:", error);
            });
    }
}

window.onload = function () {
    var billingState = document.querySelector("select#_billing_state");
    var billingCity = document.querySelector("select#_billing_city");
    var shippingState = document.querySelector("select#_shipping_state");
    var shippingCity = document.querySelector("select#_shipping_city");

    getSelectedArea(billingCity, selectBillingStateValue, selectBillingCityName, selectBillingCityValue);
    getSelectedArea(shippingCity, selectShippingStateValue, selectShippingCityName, selectShippingCityValue);

    console.log("Billing City is  " + selectBillingCityName);
    console.log("Shipping City is  " + selectShippingCityName);

    if (billingState) {
        billingState.onchange = function () {
            getAreas(billingCity, billingState.value);
        };
    }

    if (shippingState) {
        shippingState.onchange = function () {
            getAreas(shippingCity, shippingState.value);
        };
    }
};

jQuery(document).ready(function ($) {
    $("select#_billing_city, select#_shipping_city").select2({
        placeholder: {
            id: "", // the value of the option
            text: "اختر مدينة",
        },
        allowClear: true,
        templateResult: function (option) {
            if (option.text && option.text.includes("( خارج التغطية )")) {
                return $('<span style="color: #c5c5c5"></span>').text(option.text);
            } else {
                return option.text;
            }
        },
    });
});
