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
            widget.style.display = 'none';
          })
          .catch((error) => {
            console.error('Error:', error);
          });
    }
    
    
    
});
