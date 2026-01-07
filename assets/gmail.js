let emailList = [];
let nextPageToken = null;

const loadMoreBtn = document.getElementById("loadMoreBtn");

if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", () => {
        if (nextPageToken) {
            loadEmails(nextPageToken);
        }
    });
}

const sendForApprovalButton = document.getElementById('sendForApprovalButton');

if (sendForApprovalButton) {
    sendForApprovalButton.addEventListener("click", () => {
        fetch('/wp-admin/admin-ajax.php?action=submit_email_for_approval', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                to: document.getElementById("toEmail").value,
                subject: document.getElementById("emailSubjectTextbox").value,
                content: plainTextToHtml(document.getElementById("emailContentTextarea").value),
                signature: document.getElementById("signatureDiv").innerHTML,
                history: document.getElementById("emailHistoryDiv").innerHTML
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Email sent for approval successfully!");
            }
        });
    });
}

function plainTextToHtml(text) {
    if (!text) return "";

    const paragraphs = text.split(/\r?\n\r?\n/);

    return paragraphs
    .map(p => {
      const safe = escapeHtml(p);
      // Convert single line breaks inside a paragraph
      return `<p>${safe.replace(/\r?\n/g, "<br>")}</p>`;
    })
    .join("");
}

function loadEmails(pageToken = null) {
    const url = "/wp-admin/admin-ajax.php?action=get_gmail_messages" +
                (pageToken ? "&pageToken=" + pageToken : "");

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const emails = data.data.emails;
            nextPageToken = data.data.nextPageToken;

            // Append to local list
            emailList = emailList.concat(emails);

            // Render new rows
            appendToGrid(emails);

            // Hide button if no more pages
            if (!nextPageToken) {
                document.getElementById("loadMoreBtn").style.display = "none";
            }
        });
}

function appendToGrid(emails) {
    const tbody = document.querySelector("#emailGrid tbody");

    emails.forEach((email, index) => {
        const globalIndex = emailList.length - emails.length + index;

        const row = document.createElement("tr");
        row.dataset.index = globalIndex;

        row.innerHTML = `
            <td>${email.from}</td>
            <td>${email.subject}</td>
            <td>${email.date}</td>
        `;

        row.addEventListener("click", () => selectEmail(globalIndex));

        tbody.appendChild(row);
    });
}

function selectEmail(index) {
    const email = emailList[index];
    const subject = email.subject.startsWith("Re:") ? email.subject : "Re: " + email.subject;

    document.getElementById("toEmail").value = extractEmail(email.from);
    document.getElementById("emailSubjectTextbox").value = subject.includes("Re:") ? subject : "Re: " + subject;
    document.getElementById("emailHistoryDiv").innerHTML = formatEmailBody(email.date, email.from, email.body);
}

function extractEmail(str) {
  const match = str.match(/<([^>]+)>/);
  if (match) {
    return match[1].trim();
  }
  return str.trim();
}

function formatEmailBody(date, from, text) {
  if (!text) return "";

  // Split into paragraphs using double line breaks
  const paragraphs = text.split(/\r?\n\r?\n/);

  return 'On ' + date + ' ' + escapeHtml(from) + ' wrote:<br>' + paragraphs
    .map(paragraph => {
      const lines = paragraph.split(/\r?\n/);

      const processedLines = lines.map(line => {
        
        const match = line.match(/^(>+)\s?(.*)$/);

        if (match) {
          const depth = match[1].length;

          const content = match[2];

          // Build nested blockquotes as a single string
          let html = content;
          for (let i = 0; i < depth; i++) {
            if (match[2].length > 0) {
              html = `<blockquote>${html}</blockquote>`;
            }
          }
          return html; // always a string
        }

        return line;
      });

      return `<p>${processedLines.join('')}</p>`;
    })
    .join("");
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

async function getSignature(accessToken) {
  const res = await fetch(
    "https://gmail.googleapis.com/gmail/v1/users/me/settings/sendAs",
    {
      headers: {
        Authorization: `Bearer ${accessToken}`
      }
    }
  );

  const data = await res.json();

  // Primary Gmail address is usually the first entry
  const primary = data.sendAs.find(a => a.isPrimary) || data.sendAs[0];

  return primary.signature; // HTML signature
}

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}

function loadEmailSignature() {
    const signatureDiv = document.getElementById("signatureDiv");
    if (signatureDiv) {
        const accessToken = getCookie("google_access_token");
        getSignature(accessToken).then(signature => {
            signatureDiv.innerHTML = signature;
        });
    }
}


const sendEmailButton = document.getElementById("sendEmailButton");
if (sendEmailButton) {
    sendEmailButton.addEventListener("click", () => {
        document.getElementById("sendEmailButton").disabled = true;
        fetch('/wp-admin/admin-ajax.php?action=send_email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                emailId: document.getElementById("sendEmailId").value
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Email sent successfully!");
            }
        });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    loadEmailSignature();
});

loadEmails();
loadEmailSignature();