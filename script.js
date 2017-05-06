(function ($) {

    $(document).ready(function () {

        $('#updateoffers').click(function () {
            $(this).parents('li').hide();
            $('#ymlprogress').show();

            window.offerunlock = 'yes';

            updateoffers();

            return false;

        });


        var updateoffers = function () {

            var data = {action: $('.woocommerce form input[name="key_source"]').val() + '_ajaxUpdateOffers', unlock: offerunlock};

            console.log(data);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function (data) {

                    console.log(data);

                    window.offerunlock = 'no';

                    if (!data.ismakeyml) {
                        updateoffers();
                    } else {
                        $('#ymlprogress').hide();
                        $('#updateoffers').parents('li').show();
                    }
                }
            })
                .done(function () {
                    console.log("success");
                })
                .fail(function (data) {
                    $.post(ajaxurl, {action: 'yml_send_log'});
                    alert('Сталася помилка. LOG файли роботи скрипта вже пішли на пошту розробнику info@promuabot.com, він їх обробляє і скоро з Вами зв\'яжеться');
                })
                .always(function () {
                    console.log("complete");
                });
        };


        $('#add_source').click(function () {

            var name = prompt('Введіть ім\'я нового джерела');

            if (typeof name != 'object') {

                $.post(ajaxurl, {action: 'add_yml_source', name: name}, function (data, textStatus, xhr) {
                    window.location.href = window.location.href;
                });

            }

            return false;
        });


        if ($('.woocommerce form input[name="key_surce"]').val() != '_yandex_market')
            $('.woocommerce form p.submit input[name="save"]').replaceWith(
                '<input name="save" class="button-primary" type="submit" value="Зберегти зміни"> <input name="delete" class="button-primary delete" type="submit" value="Видалити">'
            );

        $('.woocommerce form p.submit input[name="delete"]').live('click', function (event) {

            if (confirm('Ви дійсно хочете видалити джерело?')) {
                $.post(ajaxurl, {action: 'del_yml_source', key: $('.woocommerce form input[name="key_source"]').val()}, function (data, textStatus, xhr) {
                    window.location.href = window.location.href + "&source=_yandex_market";
                });
            }


            return false;
        });


    });
})(jQuery);