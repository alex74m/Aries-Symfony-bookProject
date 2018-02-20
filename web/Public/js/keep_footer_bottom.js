(function($, window, document, undefined){
    $(function($){

        function fixedFooter(){
            
            var heightWindow = jQuery(window).height();
            var heightDocument = jQuery(document).height();
            var heightBody = jQuery("body").outerHeight(true);
            var heightFooter = jQuery("#footer").height();
            var heightFooterWithMarge = jQuery("#footer").outerHeight();
            var marginFooter = heightFooterWithMarge - heightFooter;

            if (heightDocument == heightWindow) {
                jQuery("#footer").css('position', 'fixed');
                jQuery("#footer").css('bottom', '0px');
            }else if ((heightWindow - heightBody) > 0 && (heightWindow - heightBody + heightFooterWithMarge) > marginFooter) {
                    jQuery("#footer").css('position', 'fixed');
                    jQuery("#footer").css('bottom', '0px');              
            }else{
                jQuery("#footer").css('position', 'relative');
                jQuery("#footer").css('bottom', '0px');
            }
        };

        fixedFooter();
        jQuery(window).resize(function() {
          fixedFooter();
        });
        jQuery(window).click(function() {
          fixedFooter();
        });
    });
})(jQuery, window, document);
