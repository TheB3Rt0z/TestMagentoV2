require(["jquery", 'Magento_Ui/js/modal/alert', 'mage/url', 'mage/translate'], function ($, alert, urlBuilder, $t) {

    function fillList(el)
    {
        el = $(el);
        let wishlistitemid = el.data("wishlistitemid");
        if (wishlistitemid) {
            const code = el.data('code')
            $.ajax(BASE_URL + '/wishlistext/participant/get', {
                data: {
                    wishlist_item_id: wishlistitemid,
                    code: code
                },
                success: function (data) {
                    let conteinderId = '#participant_list_' + wishlistitemid;

                    if (data.success) {
                        el.empty();
                        for (let i = 0; i < data.data.length; i++) {
                            const part = data.data[i];
                            let delBtn = '';


                            let item = '<div><span><label>Name: </label>' + part.name + '</span>';
                            if (part.email) {
                                item += '<span>(' + part.email + ')</span>';
                            }
                            item += '<span> <label>Menge:</label> ' + part.qty + '</span>';
                            if (!code) {
                                item += ' <a href="#" data-participantid="' + part.id + '"><span class="icon-remove"></span> <label>Teilnehmer entfernen</label></a>';
                            }
                            item += '</div>';
                            if (part.comment) {
                                item += '<span> <label>Bemerkung:</label> ' + part.comment + '</span>';
                            }
                            let entry = $('<li>' + item + '</li>');
                            el.append(entry)
                        }
                        el.find('a').click(function (event) {
                            event.preventDefault()
                            $.ajax(BASE_URL + '/wishlistext/participant/remove', {
                                data: {id: $(event.currentTarget).data('participantid')},
                                showLoader: true,
                                success: function () {
                                    fillList(el);
                                }
                            });
                        });

                        $.each(data.totals, function (index, value) {
                            $(conteinderId + ' .wishlist-participants-total .' + index + ' span').text(value);
                        })

                        if (data.data.length > 0) {
                            $(conteinderId + ' .wishlist-participants-title').show();
                            $(conteinderId + ' .wishlist-participants-title-empty').hide();
                            $(conteinderId + ' .wishlist-participants-total').show();
                        } else {
                            $(conteinderId + ' .wishlist-participants-title').hide();
                            $(conteinderId + ' .wishlist-participants-title-empty').show();
                            $(conteinderId + ' .wishlist-participants-total').hide();
                        }
                    } else {
                        $(conteinderId + ' .wishlist-participants-title').hide();
                        $(conteinderId + ' .wishlist-participants-title-empty').show();
                    }
                }
            });
        }
    }

    $('body').ready(function () {
            $('.wishlist-participants-list').map(function (i, el) {
                fillList(el);
            });

            var addParticipant = function (data, callback) {
                $.ajax(BASE_URL + '/wishlistext/participant/add', {
                    type: "POST",
                    data: data,
                    showLoader: true,
                    success: function (response) {
                        callback(response);
                    }
                });
            }

            // $('.qty-validation').keyup(function (event) {
            //     let element = $(event.target);
            //     element.val(element.val().replace(/[^\d.-]/g, ''));// (/[^0-9.]/g, ''));
            // })

            $('.participant_form').submit(function (event) {
                event.preventDefault();

                let form = $(this);
                let data = form.serializeArray();
                let wishlistitemid = form.data("wishlistitemid");

                if (form.valid() && wishlistitemid) {
                    addParticipant(data, function (response) {
                        if (response.type == 'success') {
                            form.find('.dinamically').val('');
                            $('.wishlist-participants-list[data-wishlistitemid="' + wishlistitemid + '"]').map(function (i, el) {
                                fillList(el);
                            });
                        } else {
                            alert({
                                title: "Error",
                                content: response.text,
                                autoOpen: true,
                                clickableOverlay: false,
                                focus: ""
                            });
                        }
                    });
                }

                return false;
            })

            $('.wishlist-participant-owner-add').click(function () {
                event.preventDefault();

                let containerElement = $(this).parents('.owner-add-participant')[0];
                let container = $(containerElement);

                let valid = true;
                let errorMessage = '<div generated="true" class="mage-error" style="display: block;">Vervollständigen Sie bitte die notwendigen Angaben.</div>';
                let data = {};
                container.find('.mage-error').remove();
                $.each(container.find('.senditem'), function () {
                    data[$(this).data('name')] = $(this).val();
                    if (!$(this).val() && $(this).data('required') == true) {
                        $(this).parent().append(errorMessage);
                        valid = false;
                    }
                })

                let wishlistitemid = data.wishlist_item_id;

                if (valid && wishlistitemid) {
                    addParticipant(data, function (response) {
                        if (response.type == 'success') {
                            container.find('.dinamically').val('');
                            $('.wishlist-participants-list[data-wishlistitemid="' + wishlistitemid + '"]').map(function (i, el) {
                                fillList(el);
                            });
                        } else {
                            alert({
                                title: $t("That did not work"),
                                content: response.text,
                                autoOpen: true,
                                clickableOverlay: false,
                                focus: ""
                            });
                        }
                    });
                }
                return false;
            })
    });
});