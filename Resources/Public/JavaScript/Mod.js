import AjaxRequest from "@typo3/core/ajax/ajax-request.js"
import Notification from "@typo3/backend/notification.js"
import Icons from "@typo3/backend/icons.js"
import $ from 'jquery';

$(document).on('click', '[data-save]', function () {
  $($(this).data('save')).submit();
});


$(document).on('click', '[data-translate-to]', function () {
  var $self = $(this);
  var text = $($self.data('source')).val();
  var target = $self.data('translate-to');

  Icons.getIcon('spinner-circle-dark', Icons.sizes.small).then(function (markup) {
    $self.html(markup);
  });

  new AjaxRequest(TYPO3.settings.ajaxUrls.formtranslator_translate)
    .withQueryArguments({ text: text, target: target })
    .get()
    .then(async function (response) {
      const resolved = await response.resolve();
      $($self.data('target')).val(resolved.translation);

      Icons.getIcon('actions-translate', Icons.sizes.small).then(function (markup) {
        $self.html(markup);
      });

    }, function (error) {
      Notification.error('', `The request failed with ${error.response.status}`);
    });
});
