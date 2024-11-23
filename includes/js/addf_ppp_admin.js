jQuery(function ($) {
 
    $('#addf_prc_categories').select2({
       
        allowClear: true
    });
     $('#user_roles_select').select2({
       
        allowClear: true
    });
      $('#user_countries_select').select2({
        
        allowClear: true
    });
     $('#order_statuses_select').select2({
       
        allowClear: true
    });

    $('#offer_description').click(function() {
    offer_des_show_hide();
  });


    function offer_des_show_hide(){
       if ($('#offer_description').is(':checked')) {
        $('#offer_description_lenght_tr').show();
        } else {
        $('#offer_description_lenght_tr').hide();
        }
    }

    $('.discount_amount').on('input', function() {
       // Get the data-product-amount attribute value
       var productAmount = $(this).attr('data-product-amount');
       var productid = $(this).attr('data-product-id');
       var discounttypeget = $('.price_type_'+ productid).val();
   
       // Get the current value of the input
       var currentValue = $(this).val();

       if (discounttypeget=== 'p_d') {
           $(this).attr('max', '100');
       }
      else{
       // Check if the current value is greater than the data-product-amount value
       if (parseFloat(currentValue) > parseFloat(productAmount)) {
           // Set the input value to the data-product-amount value
           // $(this).val(productAmount);
           $(this).attr('max', productAmount);
       }
   }
    });

    $('.discount_amount').change(function() {
        // Get the data-product-amount attribute value
        var productAmount = $(this).attr('data-product-amount');
        var productid = $(this).attr('data-product-id');
        var discounttypeget = $('.price_type_'+ productid).val();
    
        // Get the current value of the input
        var currentValue = $(this).val();

        if (discounttypeget=== 'p_d') {
            $(this).attr('max', '100');
        }
       else{
        // Check if the current value is greater than the data-product-amount value
        if (parseFloat(currentValue) > parseFloat(productAmount)) {
            // Set the input value to the data-product-amount value
            // $(this).val(productAmount);
            $(this).attr('max', productAmount);
        }
    }
    });
offer_des_show_hide();


            $(function () {
                $('.js_multipage_select_product').select2({
                    ajax: {
                        dataType: "json",
                        url: ajaxurl,
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term,
                                    action: 'post_purchase_offer_getproductsearch' 
                                };
                            },
                            processResults: function( data ) {
                            var options = [];
                            if ( data ) {
                                $.each( data, function( index, text ) { 
                                    options.push( { id: text[0], text: text[1]  } );

                                });
                                // console.log(options);
                            }
                            return {
                                results: options
                            };
                            // console.log(options);
                        },
                        cache: true
                    },
                    minimumInputLength: 3
                });
            });


             $(function () {
                $('.js_multipage_select_product_offer').select2({
                    ajax: {
                        dataType: "json",
                        url: ajaxurl,
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term,
                                    action: 'post_purchase_offer_getproductsearch_2' 
                                };
                            },
                            processResults: function( data ) {
                            var options = [];
                            if ( data ) {
                                $.each( data, function( index, text ) { 
                                    options.push({ id: text[0], text: text[1], customData: text[2]});

                                });
                                // console.log(options);
                            }
                            return {
                                results: options
                            };
                            // console.log(options);
                        },
                        cache: true
                    },
                    minimumInputLength: 3
                });
            });




 $(".add_options").click(function(){
    // Getting values from input fields
    var selected_products = $(".js_multipage_select_product_offer").val(); // Get selected values
    
    // Clear existing rows in the table
    
    $(".responsive-table").show();
    // $(".responsive-table tbody").empty();
    
    // Append new rows based on selected values
    if (selected_products) {
        selected_products.forEach(function(value) {
            // Check if the row already exists
            if ($('.responsive-table tbody').find('input[value="' + value + '"]').length === 0) {
                // Getting the text of the selected option
                var selected_option_text = $('.js_multipage_select_product_offer option[value="' + value + '"]').text();
                var selectedOption = $('.js_multipage_select_product_offer').select2('data').find(function(option) {
                    return option.id == value;
                });
                var selectedOptionData = selectedOption.customData;
        
                
                $(".responsive-table tbody").append(
                    '<tr>' +
                    '<td class="table-value">' 
                    + selected_option_text + ' <input name="post_purchase_select_products[' + value + '][product_id]" value="' + value + '" type="hidden" >'+
                    '<input name="post_purchase_select_products[' + value + '][product_title]" value="' + selected_option_text + '" type="hidden" >'+
                   '</td>' +
                    '<td class="table-value">' +
                    '<select name="post_purchase_select_products[' + value + '][discount_type]" class="wc-enhanced-select" aria-hidden="true">' +
                    '<option value="p_d"  selected="selected">Percentage discount</option>' +
                    '<option value="f_d">Fixed discount</option>' +
                    '<option value="f_p">Fixed price</option>' +
                    '</select>' +
                    '</td>' +
                    '<td class="table-value"><input name="post_purchase_select_products[' + value + '][custom_quantity]" type="number" value="1" min="1"></td>' +
                    '<td class="table-value"><input type="number" value="' + selectedOptionData + '" readonly></td>' +
                    '<td class="table-value"><input name="post_purchase_select_products[' + value + '][discount_amount]" type="number" value="0" min="0"></td>' +
                    '<td class="table-value"><button class="delete-button">X</button></td>' +
                    '</tr>'
                );
            }
        });
        
        // Clear the input field after appending rows

       $('.js_multipage_select_product_offer').val('').trigger('change'); // Clear selected values
        
    }
}); 







$(document).on('click', '.delete-button', function() {
    $(this).closest('tr').remove(); // Remove the closest <tr> element containing the clicked delete button
    // Check if there are any remaining rows
    if ($(".responsive-table tbody tr").length === 0) {
        $(".responsive-table").hide(); // Hide the table if no rows are remaining
    }
});





});