define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',

    ], function ($, Component, ko, quote) {  
        'use strict';

    return Component.extend({
        defaults: {
            template: 'Wuunder_Wuunderconnector/checkout/shipping/parcelshop',
        },

        initialize: function () {
            this._super();
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
                var parcelshopAddress;
                var baseUrl = window.checkoutConfig.backend_base_url;
                var baseUrlApi = window.checkoutConfig.api_base_url;
                var availableCarrierList;
                var setParcelshopId = "wuunder/index/parcelshop/setParcelshopId";

                // add selected parcelshop to page
                function _printParcelshopAddress() {
                    console.log(parcelshopAddress);
                    if (parcelshopAddress) {
                        if (window.parent.document.getElementsByClassName("parcelshopInfo").length) {
                            window.parent.document.getElementsByClassName("parcelshopInfo")[0].remove();
                        }
                        var currentParcelshop = document.createElement('div');
                        currentParcelshop.className += 'parcelshopInfo';
                        currentParcelshop.innerHTML = '<br/><strong>Huidige Parcelshop:</strong><br/>' + parcelshopAddress;
                        window.parent.document.getElementById('wuunder_parcelshop_container').appendChild(currentParcelshop);
                        window.parent.document.getElementById('get_parcels_link').innerHTML = 'klik hier om een andere parcelshop te kiezen';

                    }
                }


                function _showParcelshopLocator() {
                    if (quote.shippingAddress().isDefaultShipping()) {
                        let shippingAddressFromQuote = quote.shippingAddress().getAddressInline();
                        let shippingAddress = shippingAddressFromQuote.match(/.*?\,\ (.*?)\,\ (.*?)\,\ *(.*?)\,\ (.*?)$/);
                        var urlAddress = encodeURI(shippingAddress[1] + ' ' + shippingAddress[3] + ' ' + shippingAddress[2] + ' ' + shippingAddress[4]);    
                    } else {
                        let shippingAddress = quote.shippingAddress();
                        var urlAddress = encodeURI(shippingAddress.street + ' ' + shippingAddress.postcode + ' ' + shippingAddress.city + ' ' + shippingAddress.countryId);    
                    }
                    _openIframe(urlAddress);
                }


                function _openIframe(urlAddress) {
                    //var iframeUrl = baseUrlApi + 'parcelshop_locator/iframe/?lang=nl&availableCarriers=' . availableCarrierList . '&address=' + urlAddress;
                    var iframeUrl = baseUrlApi + 'parcelshop_locator/iframe/?lang=nl&availableCarriers=dpd,postnl&address=' + urlAddress;
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
                                'quoteId' : quote.getQuoteId(),
                        }, function( data ) {
                                parcelshopAddress = _markupParcelshopAddress(data);
                                _printParcelshopAddress();
                            });
                }

                function _markupParcelshopAddress(parcelshopData) {
                                let data = JSON.parse(parcelshopData);
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