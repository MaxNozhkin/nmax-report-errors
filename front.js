(function($) {

	var Report_Error = {

		onReady: function() {
			$(document).keyup(function (ev) {
				if (ev.keyCode === 13 && ev.ctrlKey && ev.target.nodeName.toLowerCase() !== 'textarea') {
					var report = Report_Error.getSelectionParentElement();
					if (report.selection.length > 0 && report.selection.length < 200 ) {
						Report_Error.reportSpellError(report);
					}
				}
			});
		},

		getSelectionParentElement: function() {
			var parentEl, sel;
			if (window.getSelection) {
				sel = window.getSelection();
				if (sel.rangeCount) {
					parentEl = sel.getRangeAt(0).commonAncestorContainer;
					if (parentEl.nodeType != 1) {
						parentEl = parentEl.parentNode;
					}
				}
				sel = sel.toString();
			} else if ( (sel = document.selection) && sel.type != 'Control') {
				parentEl = sel.createRange().parentElement();
				sel = sel.createRange().text;
			}
			parentEl = parentEl.innerText;
			return { 'selection': sel, 'context': parentEl };
		},

		reportSpellError: function(report) {
			if ( report.hasOwnProperty('selection') && report.hasOwnProperty('context')) {
				
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: nmaxReportErrorsArgs.ajaxurl,
					data: {
						action: 'nmax_report_errors',
						reported_text: report.selection,
						context: report.context,
						nonce: nmaxReportErrorsArgs.nonce
					},
					success: function (response) {
						if(typeof nmax_report_errors_modal == 'function'){
							nmax_report_errors_modal(response);
						} else {
							alert('Сообщение об ошибке успешно отправлено!');
						}
					}
				})
			}
		}
	};

	$( document ).ready( Report_Error.onReady );

})(jQuery);
