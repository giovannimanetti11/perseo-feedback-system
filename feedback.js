document.addEventListener('DOMContentLoaded', function() {
    var widget = document.getElementById('perseo-feedback-widget');
    var yesButton = document.getElementById('perseo-feedback-yes');
    var noButton = document.getElementById('perseo-feedback-no');
    var closeButton = document.getElementById('perseo-feedback-close');

    // Check if feedback was already given
    var feedbackGiven = document.cookie.split('; ').find(row => row.startsWith('perseo_feedback_given'));
    if (feedbackGiven) {
        widget.style.display = 'none';
        return;
    }

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

    closeButton.addEventListener('click', function() {
        hideWidget();
    });

    function sendFeedback(feedback) {
        var data = {
            url: window.location.href,
            feedback: feedback
        };
    
        //console.log('Sending feedback:', data);
    
        fetch('/wp-json/perseo/v1/feedback', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': wpApiSettings.nonce,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(response => response.json())
          .then(data => {
            //console.log('Success:', data);
            document.cookie = "perseo_feedback_given=true; max-age=604800; path=/";
            hideWidget();
          })
          .catch((error) => {
            console.error('Error:', error);
          });
    }

    function hideWidget() {
        widget.style.display = 'none';
    }
});


