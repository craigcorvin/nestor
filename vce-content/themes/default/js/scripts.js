$(document).ready(function() {
	$('.responsive-menu-icon').on('click', function () {
		$('.responsive-menu').slideToggle();
	});

	$('.coaching-partnership-members:not(:last-child)').after('<span class="plus-sign">+</span>');

	// add/remove aria-expanded label for sub-menu
	$('.menu-main .menu-item-has-children, .sub-menu').on('mouseenter focus', function() {
		$('.menu-level-1').attr('aria-expanded', 'true');
	}).on('mouseleave blur', function() {
		$('.menu-level-1').removeAttr('aria-expanded');
	});

	// move cultivate learning media library up
	$('.all-resources-button').before($('.cl-media-library'));


	// tooltips
	$.each(['supporting-documentation', 'shared-goals', 'competencies', 'action-plan-steps', 'action-plan-resource', 'focused-observation', 'reflection-and-feedback'], function(i, tooltipType) {
		$('.pbc-item-header.' + tooltipType).append(
			$('<button class="info-tooltip link-button ' + tooltipType + '-tooltip">').text('Learn More')
		);

		var tooltip;

		switch (tooltipType) {
		case 'supporting-documentation':
			tooltip = $('<div class="instructions-container ' + tooltipType + '-instructions">').append(
				$('<div class="instructions-title">').text('What is Supporting Documentation?')
			).append(
				$('<div class="instructions-text">')
					.text('(Optional) Add documentation that supports the development of coaching cycles (needs assessment, baseline video, or other teacher and child data).')
			);
			break;
		case 'shared-goals':
			tooltip = $('<div class="instructions-container ' + tooltipType + '-instructions">').append(
				$('<div class="instructions-title">').text('What are Shared Goals?')
			).append(
				$('<div class="instructions-text">')
					.text('Create a goal that will start a coaching cycle of acquiring knowledge, seeing the practice in others, practicing, and reflecting.')
			);
			break;
		case 'competencies':
			tooltip = $('<div class="instructions-container ' + tooltipType + '-instructions">').append(
				$('<div class="instructions-title">').text('What are Competencies?')
			).append(
				$('<div class="instructions-text">')
					.text('Competencies')
			);
			break;
		case 'action-plan-steps':
			tooltip = $('<div class="instructions-container ' + tooltipType + '-instructions">').append(
				$('<div class="instructions-title">').text('Add Action Plan Steps')
			).append(
				$('<div class="instructions-text">')
					.text('Create Action Plan Steps that help achieve the goal. Action Plan Steps can include shared resources or focused observations.')
			);
			break;
		case 'action-plan-resource':
			tooltip = $('<div class="instructions-container ' + tooltipType + '-instructions">').append(
				$('<div class="instructions-title">').text('Shared Resource')
			).append(
				$('<div class="instructions-text">')
					.text('Shared resources help build knowledge and skill, and see the practice in action.')
			);
			break;
		case 'focused-observation':
			tooltip = $('<div class="instructions-container ' + tooltipType + '-instructions">').append(
				$('<div class="instructions-title">').text('Focused Observation')
			).append(
				$('<div class="instructions-text">')
					.text('The focused observation shares documentation around trying the practice and seeking reflection and feedback.')
			);
			break;
			case 'reflection-and-feedback':
				tooltip = $('<div class="instructions-container ' + tooltipType + '-instructions">').append(
					$('<div class="instructions-title">').text('Reflection and Feedback')
				).append(
					$('<div class="instructions-text">')
						.text('Reflect and provide feedback about this Focused Observation.')
				);
				break;
		default:
			console.log('no tooltip available');
		}

		$('.info-tooltip.' + tooltipType + '-tooltip, .select-asset-type.' + tooltipType)
			.data('my-tooltip', tooltip).on('mouseover focus', function() {
				$('.' + tooltipType + '-instructions').show();
			}).on('click', function() {
					$('.' + tooltipType + '-instructions').toggle();
			}).blur(function() {
				$('.' + tooltipType + '-instructions').hide();
			}).mouseout(function() {
				$('.' + tooltipType + '-instructions').hide();
			});

		$('.pbc-item-header.' + tooltipType + ', .select-asset-type.' + tooltipType).append(
			$('.info-tooltip.' + tooltipType + '-tooltip, .select-asset-type.' + tooltipType).data('my-tooltip')
		);
	});

	// $('.media-add-component').before($(`<button class="open-app-button" type="button" onclick="javascript:location.href='exp://ce-ruj.dev-mev.cc-app-expo.exp.direct';">`).text("Upload (opens app)"));
});
