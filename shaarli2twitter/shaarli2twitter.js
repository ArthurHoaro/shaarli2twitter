document.addEventListener('DOMContentLoaded', function(event) {
  var privateInput = document.getElementsByName('lf_private')[0];
  var tweetInput = document.getElementsByName('tweet')[0];
  if (tweetInput == null) {
    return;
  }

  privateInput.addEventListener('click', function (event) {
    tweetInput.disabled = privateInput.checked;
  });

  var textareaZone = document.getElementById('tweet-textarea');
  var textarea = document.querySelector('#tweet-textarea textarea');

  document.getElementById('s2t-edit').addEventListener('click', function (event) {
    event.preventDefault();
    if (textarea.disabled === false) {
      textareaZone.classList.add('hidden');
      textarea.disabled = true;
      event.target.innerHTML = 'edit';
    } else {
      textareaZone.classList.remove('hidden');
      textarea.disabled = false;
      event.target.innerHTML = 'cancel';
    }
  });

  var injectors = document.querySelectorAll('.s2t-inject');
  [].slice.call(injectors).forEach((injector) => {
    injector.addEventListener('click', function (event) {
      event.preventDefault();
      textarea.value += ' '+ event.target.getAttribute('data-content');
    });
  });
});
