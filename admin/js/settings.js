(function () {
  function getSelectedBoutenStyle() {
    var selected = document.querySelector('input[name="rubymaco_bouten_style"]:checked');

    return selected && selected.value === 'sesame' ? 'sesame' : 'dot';
  }

  function updateBoutenPreviews(style) {
    document.querySelectorAll('.rubymaco-bouten').forEach(function (preview) {
      preview.classList.remove('rubymaco-bouten--dot', 'rubymaco-bouten--sesame');
      preview.classList.add('rubymaco-bouten--' + style);
    });
  }

  document.addEventListener('change', function (event) {
    var target = event.target;

    if (! target || target.name !== 'rubymaco_bouten_style') {
      return;
    }

    updateBoutenPreviews(getSelectedBoutenStyle());
  });
})();
