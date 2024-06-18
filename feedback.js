document.addEventListener('DOMContentLoaded', function() {

    var widget = document.getElementById('perseo-feedback-widget');
    var yesButton = document.getElementById('perseo-feedback-yes');
    var noButton = document.getElementById('perseo-feedback-no');
    var closeButton = document.getElementById('perseo-feedback-close');
    var commentBox = document.getElementById('perseo-feedback-comment');
    var submitButton = document.getElementById('perseo-feedback-submit');

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
        toggleFeedbackElements();
        yesButton.classList.add('selected');
    });

    noButton.addEventListener('click', function() {
        toggleFeedbackElements();
        noButton.classList.add('selected');
    });

    closeButton.addEventListener('click', function() {
        hideWidget();
    });

    submitButton.addEventListener('click', function() {
        var feedback = yesButton.classList.contains('selected') ? 'yes' : 'no';
        var comment = commentBox.value;
        sendFeedback(feedback, comment);
    });

    function toggleFeedbackElements() {
        var question = document.querySelector('#perseo-feedback-widget span');
        var followupText = perseoSettings.followupText || "Lascia un commento per aiutarci a migliorare.";
        var buttonsContainer = document.querySelector('.buttons-container');
    
        question.textContent = followupText;
        buttonsContainer.style.display = 'none';
        commentBox.style.display = 'block';
        submitButton.style.display = 'block';
    }

    function sendFeedback(feedback, comment) {
        var data = {
            url: window.location.href,
            feedback: feedback,
            comment: comment
        };

        fetch('/wp-json/perseo/v1/feedback', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': perseoSettings.nonce,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        }).then(data => {
            document.cookie = "perseo_feedback_given=true; max-age=604800; path=/";
            showThankYouMessage();
        }).catch((error) => {
            console.error('Error:', error);
        });
    }


    
    function showThankYouMessage() {
        // Hides textarea and submit button
        commentBox.style.display = 'none';
        submitButton.style.display = 'none';

        // Show thank you message
        var thankYouText = perseoSettings.thankYouText || "Ti ringraziamo per il tuo feedback.";
        var question = document.querySelector('#perseo-feedback-widget span');
        question.textContent = thankYouText;
    }
    

    function hideWidget() {
        widget.style.display = 'none';
    }
});
