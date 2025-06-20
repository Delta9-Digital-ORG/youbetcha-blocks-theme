jQuery(function () {
	let productCategoryList;
	let productCategoryArray = new Array();
	
	productCategoryList = $('.product-page-description-pattern').children('.product-categories-container').children('.wc-block-product-categories').children('ul');
	
	productCategoryList.children().each(function () {
		let categoryName = $(this).children(":first-child").children(":first-child").text().toLowerCase();
		
		if(categoryName == 'cana facts' || categoryName == 'ingredients') {
			$(this).children(":first-child").remove(); // Remove the Category Name
			
			$(this).children().each(function () {
				$(this).children().each(function () {
					$(this).children().each(function () {
						$(this).children().each(function () {
							let textVal = $(this).children(":first-child").children(":first-child").text().toLowerCase();
							
							if(textVal == '2.5mg' || textVal == '5mg' || textVal == '10mg') {
								$(this).remove(); // Remove the milligram values
							}
						});
					});
					
					productCategoryArray.push(this); // Push the inner lists to the array
				});
			});
		}
	});
	
	productCategoryList.empty();
	
	$(productCategoryArray).each(function () {
		$(this).appendTo(productCategoryList); // Update the HTML for the list to be displayed
	});
});