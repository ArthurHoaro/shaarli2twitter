var privateInput = document.getElementsByName('lf_private')[0];
var tweetInput = document.getElementsByName('tweet')[0];

privateInput.addEventListener('click', function(event) {
    tweetInput.disabled = privateInput.checked;
});
