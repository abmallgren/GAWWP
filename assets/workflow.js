document.getElementById('topAppoveButton').addEventListener('click', function() {
    if(confirm('Are you sure you want to approve this email?')) {
        const params = new URLSearchParams(window.location.search);
        const emailId = params.get('email_id') ? params.get('email_id') : document.getElementById('emailApprovalId').value;
        fetch('/wp-admin/admin-ajax.php?action=approve_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            emailId: emailId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Approved successfully!');
        }
    });
    }
});
document.getElementById('topRejectButton').addEventListener('click', function() {
    document.getElementById('feedbackDiv').style.display = 'block';
});
document.getElementById('submitFeedbackButton').addEventListener('click', function() {
    const params = new URLSearchParams(window.location.search);
    const emailId = params.get('email_id') ? params.get('email_id') : document.getElementById('emailApprovalId').value;
    const subject = document.getElementById('subjectSpan').innerText;
    const feedback = document.getElementById('feedbackTextarea').value;
    fetch('/wp-admin/admin-ajax.php?action=submit_email_feedback', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            emailId: emailId,
            feedback: feedback
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Feedback submitted successfully!');
        }
    });
});
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}