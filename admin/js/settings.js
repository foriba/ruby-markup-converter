(function () {
  function getSelectedBoutenStyle() {
    var selected = document.querySelector('input[name="rbmkup_bouten_style"]:checked');

    return selected && selected.value === 'sesame' ? 'sesame' : 'dot';
  }

  function updateBoutenPreviews(style) {
    document.querySelectorAll('.rubymarkup-bouten').forEach(function (preview) {
      preview.classList.remove('rubymarkup-bouten--dot', 'rubymarkup-bouten--sesame');
      preview.classList.add('rubymarkup-bouten--' + style);
    });
  }

  document.addEventListener('change', function (event) {
    var target = event.target;

    if (! target || target.name !== 'rbmkup_bouten_style') {
      return;
    }

    updateBoutenPreviews(getSelectedBoutenStyle());
  });
})();
