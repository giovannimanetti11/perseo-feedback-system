document.addEventListener('DOMContentLoaded', function() {
    var widget = document.getElementById('perseo-feedback-widget');
    var yesButton = document.getElementById('perseo-feedback-yes');
    var noButton = document.getElementById('perseo-feedback-no');

    // Widget delay of 5s (5000 milliseconds)
    setTimeout(function() {
        widget.classList.add('show');
    }, 5000);

    yesButton.addEventListener('click', function() {
        sendFeedback('yes');
    });

    noButton.addEventListener('click', function() {
        sendFeedback('no');
    });

    function sendFeedback(feedback) {
        wp.apiFetch({
            path: '/wp-json/perseo/v1/feedback',
            method: 'POST',
            data: {
                url: window.location.href,
                feedback: feedback
            }
        }).then(function() {
            widget.style.display = 'none';
        });
    }
});
