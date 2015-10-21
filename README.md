# nmax-report-errors
Wordpress plugin for report errors by "ctrl + enter", has table in adminpanel and send mail notification for post author.

For custom ajax callback, add function **nmax_report_errors_modal** in your theme, for example:

```
function nmax_report_errors_modal(response){
  $.modal({
    title: response ? "Success" : "Fail",
    description: response ? 'true' : 'false',
    close: "toggle",
    content: '<div class="report-errors-modal"></div>'
  });
}
```
