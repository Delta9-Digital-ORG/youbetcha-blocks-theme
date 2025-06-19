jQuery(function () {
	let productCategoryList;
	let productCanaFactsArray = new Array();
	let productNutritionFactsArray = new Array();
	
	productCanaFactsCategoryList = $('.product-page-cana-facts-pattern').children('.product-categories-container').children('.wc-block-product-categories').children('ul');
	productNutritionFactsCategoryList = $('.product-page-nutrition-facts-pattern').children('.product-categories-container').children('.wc-block-product-categories').children('ul');
	
	productCanaFactsCategoryList.children().each(function () {
		let categoryName = $(this).children(":first-child").children(":first-child").text().toLowerCase();
		
		if(categoryName == 'cana facts' || categoryName == 'nutrition facts') {
			$(this).children('ul').each(function () {
				$(this).children('li').each(function () {
					let factName = $(this).children('a').text();
					
					$(this).children().each(function () {
						$(this).children().each(function () {
							let textVal = $(this).children(":first-child").children(":first-child").text().toLowerCase();
							
							if(categoryName == 'cana facts') {
								if(textVal == '2.5mg' || textVal == '5mg' || textVal == '10mg') {
									productCanaFactsArray.push([factName, textVal]);
								}
							} else if(categoryName == 'nutrition facts' && textVal != '') {
								productNutritionFactsArray.push([factName, textVal]);
							}
						});
					});
				});
			});
		}
	});
	
	let productNutritionFactsContainer = productNutritionFactsCategoryList.parent();
	productNutritionFactsContainer.empty();
	
	$(productNutritionFactsArray).each(function () {
		let factName = this[0];
		let factQty = this[1];
		
		let factHtml = '<div class="nutrition-facts-pattern-container"><span>' + factName + '</span><span>' + factQty + '</span></div>';
		$(factHtml).appendTo(productNutritionFactsContainer); // Update the HTML for the list to be displayed
	});
	
	let productCanaFactsCategoryContainer = productCanaFactsCategoryList.parent();
	productCanaFactsCategoryContainer.empty();
	
	$(productCanaFactsArray).each(function () {
		let factName = this[0];
		let factQty = this[1];
		
		let factHtml = '<div class="cana-facts-pattern-container"><span>' + factName + '</span><span>' + factQty + '</span></div>';
		$(factHtml).appendTo(productCanaFactsCategoryContainer); // Update the HTML for the list to be displayed
	});
});