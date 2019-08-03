function toggleLinkDisplay(linkZone, link, textareaZone, textarea, show) {
  if (show) {
    linkZone.classList.remove('hidden')
  } else {
    linkZone.classList.add('hidden')
    toggleEditZone(linkZone, link, textareaZone, textarea, false);
  }
}

function toggleEditZone(linkZone, link, textareaZone, textarea, show) {
  if (show) {
    textareaZone.classList.remove('hidden');
    textarea.disabled = false;
    link.innerHTML = 'cancel';
  } else {
    textareaZone.classList.add('hidden');
    textarea.disabled = true;
    link.innerHTML = 'edit';
  }
}

document.addEventListener('DOMContentLoaded', function(event) {
  var privateInput = document.getElementsByName('lf_private')[0];
  var tweetInput = document.getElementsByName('tweet')[0];
  if (tweetInput == null) {
    return;
  }

  var editLinkZone = document.getElementById('s2t-edit-zone');
  var editLink = document.getElementById('s2t-edit');
  var textareaZone = document.getElementById('tweet-textarea');
  var textarea = document.querySelector('#tweet-textarea textarea');

  tweetInput.disabled = privateInput.checked;
  if (privateInput.checked) {
    toggleLinkDisplay(editLinkZone, editLink, textareaZone, textarea, false);
  }
  privateInput.addEventListener('click', function (event) {
    tweetInput.disabled = privateInput.checked;
    toggleLinkDisplay(editLinkZone, editLink, textareaZone, textarea, !tweetInput.disabled && tweetInput.checked);
  });

  tweetInput.addEventListener('click', function (event) {
    toggleLinkDisplay(editLinkZone, editLink, textareaZone, textarea, event.target.checked);
  });


  editLink.addEventListener('click', function (event) {
    event.preventDefault();
    toggleEditZone(editLinkZone, editLink, textareaZone, textarea, textarea.disabled)
  });

  var injectors = document.querySelectorAll('.s2t-inject');
  [].slice.call(injectors).forEach((injector) => {
    injector.addEventListener('click', function (event) {
      event.preventDefault();
      textarea.value += ' '+ event.target.getAttribute('data-content');
    });
  });
});
