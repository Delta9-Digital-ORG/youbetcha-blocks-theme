jQuery(function () {
	let productCategoryList;
	let productCategoryArray = new Array();
	let productIngredientsArray = new Array();
	
	productCategoryList = $('.product-page-categories-pattern').children();
	
	productCategoryList.children().each(function () {
		let categoryName = $(this).children(":first-child").children(":first-child").text().toLowerCase();
		
		if(categoryName == 'cana facts' || categoryName == 'ingredients') {
			$(this).children(":first-child").remove(); // Remove the Category Name
			
			$(this).children().each(function () {
				$(this).children().each(function () {
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