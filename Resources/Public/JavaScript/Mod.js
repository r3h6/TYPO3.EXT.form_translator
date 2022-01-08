define([
  'jquery',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Backend/Notification'
], function (
  $,
  AjaxRequest,
  Notification
) {

  $(document).on('click', '[data-save]', function () {
    $($(this).data('save')).submit();
  });


  $(document).on('click', '[data-translate-to]', function () {
    var $self = $(this);
    var text = $($self.data('source')).val();
    var target = $self.data('translate-to');
    new AjaxRequest(TYPO3.settings.ajaxUrls.formtranslator_translate)
      .withQueryArguments({ text: text, target: target })
      .get()
      .then(async function (response) {
        const resolved = await response.resolve();
        $($self.data('target')).val(resolved.translation);
      }, function (error) {
        Notification.error('', `The request failed with ${error.response.status}`);
      });
  });

});
