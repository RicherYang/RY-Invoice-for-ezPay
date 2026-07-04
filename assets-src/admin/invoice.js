import $ from 'jquery';

import './invoice.scss';

$(function () {
    if ($('#RY_IFEZPAY_get_mode').length) {
        $('#RY_IFEZPAY_get_mode').on('change', function () {
            if ($(this).val() == 'manual') {
                $('#RY_IFEZPAY_skip_foreign_order').closest('tr').hide();
                $('#RY_IFEZPAY_get_delay_time').closest('tr').hide();
            } else {
                $('#RY_IFEZPAY_skip_foreign_order').closest('tr').show();
                $('#RY_IFEZPAY_get_delay_time').closest('tr').show();
            }
        }).trigger('change');
    }

    if ($('#_invoice_type').length) {
        $(document.body).on('change', '#_invoice_type', function () {
            switch ($(this).val()) {
                case 'personal':
                    $('._invoice_carruer_type_field').show();
                    $('._invoice_no_field').hide();
                    $('._invoice_donate_no_field').hide();
                    $('#_invoice_carruer_type').trigger('change');
                    break;
                case 'company':
                    $('._invoice_carruer_type_field').hide();
                    $('._invoice_carruer_no_field').hide();
                    $('._invoice_no_field').show();
                    $('._invoice_donate_no_field').hide();
                    break;
                case 'donate':
                    $('._invoice_carruer_type_field').hide();
                    $('._invoice_carruer_no_field').hide();
                    $('._invoice_no_field').hide();
                    $('._invoice_donate_no_field').show();
                    break;
            }
        });
        $(document.body).on('change', '#_invoice_carruer_type', function () {
            switch ($(this).val()) {
                case 'ezpay_host':
                    $('._invoice_carruer_no_field').hide();
                    break;
                case 'MOICA':
                    $('._invoice_carruer_no_field').show();
                    break;
                case 'phone_barcode':
                    $('._invoice_carruer_no_field').show();
                    break;
            }
        });
        $('#_invoice_type').trigger('change');
    }

    $('.ajax-ezpay-invoice').on('click', function () {
        const action = $(this).data('action');
        if ($.inArray(action, ['get', 'cancel', 'invalid']) === -1) {
            return;
        }
        $.blockUI({
            message: RyWaiAdminInvoiceParams.i18n[action],
        });
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: `RY_IFEZPAY_${action}`,
                id: $(this).data('orderid'),
                _ajax_nonce: RyWaiAdminInvoiceParams._nonce[action]
            }
        }).always(function () {
            location.reload();
        });
    });
});
