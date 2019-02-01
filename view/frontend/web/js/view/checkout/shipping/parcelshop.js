define([
    'jquery',
    //'ko',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',

//], function ($, ko, Component, quote) {
    ], function ($, Component, ko, quote) {  
        'use strict';

    return Component.extend({
        defaults: {
            template: 'Wuunder_Wuunderconnector/checkout/shipping/parcelshop',
        },

        initialize: function () {
            this._super()
            var parcelShops = null;
            var parcelshopShippingMethodElem;
            this.selectedMethod = ko.computed(function () {
                var parcelshopShippingMethodElem = quote.shippingMethod();
                var selectedMethod = parcelshopShippingMethodElem !== null ? parcelshopShippingMethodElem.carrier_code + '_' + parcelshopShippingMethodElem.method_code : null;

                if (selectedMethod === 'parcelshop-picker_parcelshop-picker') {
                    if ($('#wuunder_parcelshop_container').length === 0) {
                        var columnCount = $('#label_method_parcelshop-picker_parcelshop-picker').parent().children().length;

                        $('<tr><td id="wuunder_parcelshop_container" colspan="' + columnCount + '"></td><tr>').insertAfter($('#label_method_parcelshop-picker_parcelshop-picker').parent());
                        $('#wuunder_parcelshop_container').html('<div id="parcelshop" class="parcelshopwrapper"><a href="#" id="get_parcels_link">' + $.mage.__('Click here to select your Parcelshop') + '</a><div id="map_container"><div id="map_canvas" class="gmaps"></div></div></div>');
                    }
                } else {
                    $('#wuunder_parcelshop_container').remove();
                    jQuery('.wuunder-shipping-information').hide();
                }
                return selectedMethod;
            }, this);

            $(document).ready(function () {
                var shippingCarrierId = ""; //hardcoden
                // Get the modal
                var shippingMethodElems = jQuery('input.delivery_option_radio');
                var shippingAddress;
                //var parcelshopAddress = _markupParcelshopAddress("");
                var baseUrl = window.checkoutConfig.backend_base_url;
                var baseUrlApi = window.checkoutConfig.api_base_url;
                console.log(baseUrl + ' ||||| ' + baseUrlApi);
                var availableCarrierList;
                var getAddressUrl = "wuunder/index/parcelshop/getAddress=1";
                var setParcelshopId = "wuunder/index/parcelshop/setParcelshopId=1";
                var addressId = "";
                function initParcelshopLocator(url, apiUrl, carrierList) {
                    console.log(parcelshopShippingMethodElem);
                    baseUrl = url;
                    baseUrlApi = apiUrl;
                    availableCarrierList = carrierList;
                    
                    jQuery('.delivery_options').append('<div class="delivery_option alternate_item parcelshop_container"></div>');

                    if (parcelshopShippingMethodElem) {
                        //parcelshopShippingMethodElem.onchange = _onShippingMethodChange;
                        if (parcelshopAddress !== "") {
                            parcelshopId = "";
                        }
                        //jQuery(shippingMethodElems).change(_onShippingMethodChange);
                        jQuery(shippingMethodElems).on('change', _onShippingMethodChange);
                        _onShippingMethodChange();
                    }
                }

                function _onShippingMethodChange() {
                    if (parcelshopShippingMethodElem.checked) {      
                        var container = document.createElement('div');
                        container.className += "chooseParcelshop";
                        container.innerHTML = '<div id="parcelshopsSelectedContainer"><a href="#/" onclick="_showParcelshopLocator()" id="selectParcelshop">Klik hier om een parcelshop te kiezen</a></div>';
                        // window.parent.document.getElementsByClassName('shipping')[0].appendChild(container);
                        window.parent.document.getElementsByClassName('parcelshop_container')[0].appendChild(container);
                        _printParcelshopAddress();
                    } else {
                        var containerElems = window.parent.document.getElementsByClassName('chooseParcelshop');
                        if (containerElems.length) {
                            containerElems[0].remove();
                        }
                    }
                }

                // add selected parcelshop to page
                function _printParcelshopAddress() {
                    if (parcelshopAddress) {
                        if (window.parent.document.getElementsByClassName("parcelshopInfo").length) {
                            window.parent.document.getElementsByClassName("parcelshopInfo")[0].remove();
                        }
                        var currentParcelshop = document.createElement('div');
                        currentParcelshop.className += 'parcelshopInfo';
                        currentParcelshop.innerHTML = '<br/><strong>Huidige Parcelshop:</strong><br/>' + parcelshopAddress;
                        window.parent.document.getElementById('parcelshopsSelectedContainer').appendChild(currentParcelshop);
                        window.parent.document.getElementById('selectParcelshop').innerHTML = 'klik hier om een andere parcelshop te kiezen';

                    }
                }


                function _showParcelshopLocator() {
                    var address = "";

                    //jQuery.post( baseUrl + getAddressUrl + "&addressId=" + addressId, function( data ) {
                        //shippingAddress = data["address1"] + ' ' + data["postcode"] + ' ' + data["city"] + ' ' + data["country"];
                        shippingAddress = "Noorderpoort 69 5916PJ Venlo Nederland";
                        _openIframe();
                    //});
                }


                function _openIframe() {
                    var iframeUrl = 'https://api-playground.wearewuunder.com/parcelshop_locator/iframe/?lang=nl&availableCarriers=dpd,postnl&address=Noorderpoort%2069%205916Pj%20Venlo%20NL';

                    var iframeContainer = document.createElement('div');
                    iframeContainer.className = "parcelshopPickerIframeContainer";
                    iframeContainer.onclick = function() { removeElement(iframeContainer); };
                    var iframeDiv = document.createElement('div');
                    iframeDiv.innerHTML = '<iframe src="' + iframeUrl + '" width="100%" height="100%">';
                    iframeDiv.className = "parcelshopPickerIframe";
                    iframeDiv.style.cssText = 'position: fixed; top: 0; left: 0; bottom: 0; right: 0; z-index: 2147483647';
                    iframeContainer.appendChild(iframeDiv);
                    window.parent.document.getElementById("chooseParcelshop").appendChild(iframeContainer);

                    function removeServicePointPicker() {
                        removeElement(iframeContainer);
                    }

                    function onServicePointSelected(messageData) {
                        removeServicePointPicker();
                        _loadSelectedParcelshopAddress(messageData.parcelshopId);
                    }

                    function onServicePointClose() {
                        removeServicePointPicker();
                    }

                    function onWindowMessage(event) {
                        var origin = event.origin,
                            messageData = event.data;
                        var messageHandlers = {
                            'servicePointPickerSelected': onServicePointSelected,
                            'servicePointPickerClose': onServicePointClose
                        };
                        if (!(messageData.type in messageHandlers)) {
                            alert('Invalid event type');
                            return;
                        }
                        var messageFn = messageHandlers[messageData.type];
                        messageFn(messageData);
                    }

                    window.addEventListener('message', onWindowMessage, false);
                }

                function _loadSelectedParcelshopAddress(id) {
                        jQuery.post( baseUrl + setParcelshopId, {
                                'parcelshopId' : id,
                        }, function( data ) {
                                console.log(data);
                                parcelshopAddress = _markupParcelshopAddress(data);
                                _printParcelshopAddress();
                            });
                }

                function _markupParcelshopAddress(parcelshopData) {
                                data = JSON.parse(parcelshopData);
                                var parcelshopInfoHtml = _capFirst(data.company_name) + "<br>" + _capFirst(data.address.street_name) +
                                " " + data.address.house_number + "<br>" + data.address.city;
                                parcelshopInfoHtml = parcelshopInfoHtml.replace(/"/g, '\\"').replace(/'/g, "\\'");
                                return parcelshopInfoHtml;
                }

                // Capitalizes first letter of every new word.
                function _capFirst(str) {
                    if (str === undefined)
                        return "";
                    return str.replace(/\w\S*/g, function (txt) {
                        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                    });
                }

                function removeElement(element) {
                    if (element.remove !== undefined) {
                        element.remove();
                    } else {
                        element && element.parentNode && element.parentNode.removeChild(element);
                    }
                    
                }

                $(document).on('click', '#get_parcels_link', function(e) {
                    _showParcelshopLocator();
                });

            });
           },


    });


});