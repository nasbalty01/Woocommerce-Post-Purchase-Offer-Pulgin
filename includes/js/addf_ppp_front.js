jQuery(function ($) {

$('.stock_quantity_custom').change(function() {
        var inputValue = parseInt($(this).val());
        var customQuantity = $(this).data('product-custom_quantity');
        var product_name = $(this).data('product-name'); 

        if (inputValue < customQuantity) {
            // If input value is less than the custom input value
            // Show an error message in the custom_wp_message div
            // showCustomNotice('Input value must be greater than or equal to ' + customQuantity, 'error');
           showCustomNotice('Minimum quantity of post purchase offer for product ' + product_name + ' is ' + customQuantity + ' quantity');
           // Set the input value to the customQuantity
            $(this).val(customQuantity);
        } else {
            // If input value is greater than or equal to the custom input value
            // Clear the message in the custom_wp_message div
            $('.custom_wp_message').empty();
        }
    });

    function showCustomNotice(message) {
        var notice = '<div class="woocommerce-error">' + message + '</div>';
        $('.custom_wp_message').html(notice);
    }


var check_offer_exist = $('.accept-offer').data('product-id');

if (typeof check_offer_exist !== 'undefined') {
    $('.overlay').fadeIn();
    $('.modal').fadeIn();
    $('.close-btn').fadeIn();
    $('.close-btn, .overlay').click(function() {
        $('.overlay').fadeOut();
        $('.modal').fadeOut();
        $('.close-btn').fadeOut();
    });
} 



$('.accept_skip_count').click(function() {
     $('.custom_wp_message').empty();
    var customId = $(this).data('product-id'); // Assuming your custom ID is stored in a data attribute
    var customIdsArray = []; // Create an array to store the IDs

    if (typeof customId === 'undefined') {
        $('.skip-all').each(function() {
            customIdsArray.push(this.id); // Push each skipAllId into the array
        });
    } else {
        customIdsArray.push(customId); // Push the customId into the array
    }

    $.ajax({
        url: php_var.ajaxurl, // WordPress AJAX URL
        type: 'POST',
        data: {
            action: 'store_custom_ids', // Action to trigger the PHP function
            nonce:php_var.nonce,
            custom_ids: customIdsArray // Data to send to the PHP function

        },
        success: function(response) {
            // console.log('Custom IDs stored: ' + customIdsArray.join(', '));
        }
    });
});




var acceptedOffers = [];

$('.accept-offer').click(function(e) {
    e.preventDefault();

    var productId = $(this).data('product-id');
    var customPrice = $(this).data('custom-price');
    var postId = $(this).data('post-id');
    var Quantity_stock_select = $('#stock_quantity_custom_' + productId).val();
    var customQuantity = $(this).data('custom-quantity');
    var isOfferProduct = 'is_offer_product';


    acceptedOffers.push({
        productId: productId,
        customPrice: customPrice,
        Quantity_stock_select:Quantity_stock_select,
        postId: postId,
        customQuantity: customQuantity,
        isOfferProduct: isOfferProduct
    });

});


    $('.read-more-button').on('click', function(event) {
        event.preventDefault();
        
        $(this).closest('.post-description').find('.full-description').slideDown();
        $('.post-description').remove();
        $('.full-description').show();
        $(this).remove();
    });

    
function processAcceptedOffers(acceptedOffers) {
         $('#processing-popup').fadeIn();

         setTimeout(function() {
        $.ajax({
            url: php_var.ajaxurl, // WordPress AJAX URL
            type: 'post',
            data: {
                action: 'add_to_cart_custom',
                nonce:php_var.nonce,
                acceptedOffers: acceptedOffers,
            },
            success: function(response) {
                //    console.log(response);
                  // $(document.body).trigger('added_to_cart');
                // Redirect to the cart page
                // window.location.href = '/cart/';
            },
            error: function(xhr, status, error) {
                // console.error(xhr.responseText);
            },
            complete: function() {
                // Hide processing popup when AJAX call is complete
                redirectToCheckout();
                $('#processing-popup').fadeOut();
            }
        });
    }, 3000);
   
}




       // Get the domain
        var domain = window.location.protocol + '//' + window.location.hostname;
        var pathArray = window.location.pathname.split('/');

        // Check if there is a folder in the URL path
        if (pathArray.length > 2) {
            // Construct the checkout page URL with the folder
            var folder = pathArray[1];
            var checkoutUrl = domain + '/' + folder + '/checkout/';
            var shopUrl = domain + '/' + folder + '/shop/';
        } else {
            // Construct the checkout page URL without the folder
            var checkoutUrl = domain + '/checkout/';
            var shopUrl = domain + '/shop/';
        }




       function redirectToCheckout() {
                $('.overlay').fadeOut();
                $('.modal').fadeOut();
            // Clear session storage
            sessionStorage.clear();
            // Redirect to the checkout page
            // Execute a function after 3 seconds
            
               window.location.href = checkoutUrl;
        }

        function redirectToShop() {
                $('.overlay').fadeOut();
                $('.modal').fadeOut();
            // Clear session storage
            sessionStorage.clear();
            // Redirect to the shop page
            // Execute a function after 3 seconds
               window.location.href = shopUrl;
           
            
        }


        

function offfer_slider(){

        var currentIndex = 0;
        var items = $('.post-content');
        items.hide();

        function showItem(index) {
        items.hide(); // Hide all items
        var currentItem = items.eq(index);
        currentItem.css('left', '100%'); // Position the current item to the right of the container
        currentItem.show(); // Show the current item
        currentItem.animate({ left: 0 }, 8000); // Slide the current item from right to left
    }


        


        function nextItem() {
            // Skip items that have been accepted
            while (sessionStorage.getItem('acceptedItem_' + currentIndex)) {
                currentIndex++;
                if (currentIndex >= items.length) {
                    // If all items have been accepted, redirect to checkout
                    // alert("All items have been accepted. Redirecting to checkout...");
                    processAcceptedOffers(acceptedOffers);
                   
                    
                    return;
                }
            }

            showItem(currentIndex);

            // Check if at least one item has been accepted
            var acceptedItemsCount = Object.keys(sessionStorage).filter(key => key.startsWith('acceptedItem_')).length;
            if (acceptedItemsCount > 0) {
                $('.skip-remaining').show(); // Show the "Skip Remaining" button
            } else {
                redirectToShop(); // Redirect to shop if no items have been accepted
            }
        }

        // Show the first item initially
        showItem(currentIndex);

        // Handle click event for the "Accept" button
        $('.accept-offer').on('click', function() {
            // Store the accepted item in session storage
            sessionStorage.setItem('acceptedItem_' + currentIndex, true);

            nextItem();
        });

        // Handle click event for the "Skip" button
        $('.skip').on('click', function() {
            // Store the skipped item in session storage
            sessionStorage.setItem('skippedItem_' + currentIndex, true);

            currentIndex++; // Move to the next item
            if (currentIndex >= items.length) {
                // If all items have been skipped, check if one or more offers have been accepted
                var acceptedItemsCount = Object.keys(sessionStorage).filter(key => key.startsWith('acceptedItem_')).length;
                if (acceptedItemsCount > 0) {
                    // If one or more offers have been accepted, redirect to checkout
                    // alert("One or more offers have been accepted. Redirecting to checkout...");
                    processAcceptedOffers(acceptedOffers);
                    
                } else {
                    // If no offers have been accepted, redirect to shop
                    // alert("All items have been skipped. Redirecting to shop...");
                    redirectToShop();
                }
            } else {
                showItem(currentIndex); // Show the next item
            }
        });

        // Handle click event for the "Skip All" button
        $('.skip-all').on('click', function() {
            // Store that all items have been skipped
            for (var i = currentIndex; i < items.length; i++) {
                sessionStorage.setItem('skippedItem_' + i, true);
            }

            // Redirect to shop
            // alert("All items have been skipped. Redirecting to shop...");
            redirectToShop();
        });

        // Handle click event for the "Skip Remaining" button
        $('.skip-remaining').on('click', function() {
            // Check if one or more offers have been accepted
            var acceptedItemsCount = Object.keys(sessionStorage).filter(key => key.startsWith('acceptedItem_')).length;
            if (acceptedItemsCount > 0) {
                // Redirect to checkout if one or more offers have been accepted
                // alert("One or more offers have been accepted. Redirecting to checkout...");
                processAcceptedOffers(acceptedOffers);
            } else {
                // Redirect to shop if no offers have been accepted
                // alert("No offers have been accepted. Redirecting to shop...");
                redirectToShop();
            }
        });

}

offfer_slider();

   });

   
        
  