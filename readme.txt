=== Gmail Email Approval ===
Contributors: anthonybmallgren
Donate link: https://buy.stripe.com/7sY6oIgk39eq4Q5cl914400
Tags: gmail, email, approval, workflow
Requires at least: 4.7
Tested up to: 6.9
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enables email approval workflow functionality in WordPress.

== Description ==

Allows the entry of a Google Client ID, Google Client Secret, and a single approver email.

Once the plugin is activated, a database table is created to track the workflow status of each email.

A page is created (/email-approval), which allows someone to sign in using Google.

Once a user is signed in, a list of the top 10 emails are pulled into the page, with a button that can be used to load more.

If the user clicks on one of the emails, the to address, subject (prepeding Re: if not already done), and the past emails in the thread are loaded into the form.

The user can author an email (whether creating a new email or replying).

Once the email has been drafted, the user can then submit the email for approval.

If submitted for approval, an email is automatically sent through the user's Gmail account with a link to review the email, and the past emails in the thread, and approve/reject the email.

If the approver rejects the email, feedback can be provided, which then sends an email through the approver's Gmail account back to the author of the email with the feedback and a link to edit the proposed email.

The author can then update the email, which can then be submitted for approval again.

If an approver approves an email, an email is sent through the approvers Gmail account to the author of the email providing a link to a page that can be used to send the email.

If the author visits the send email link, the author may once again review the email, complete with the past emails in the thread, and click a button to send the email, at which point, an email is sent through the author's Gmail account with the approver Cc'ed on the email.

A few notes about the sections above:

== Frequently Asked Questions ==

= How is data persisted on the client side? =

Cookies are used.

== Screenshots ==

1. Settings menu where the Google information and approver email address are entered.
2. Sign-in button (note, the default theme was used in this screenshot).
3. User interface in which emails are drafted.
4. Notification email which alerts the approver that approval is needed.
5. Approve/reject page which allows feedback to be given.
6. Notification email which alerts the author that feedback has been given and another revision has been requested.
7. User interfact that the user is sent to where they can revise the email being drafted.
8. Notification email which alerts the author that an email was approved.
9. User interface where the author can actually send the email.
10. The final email that was sent in this workflow.

== Changelog ==

= 1.0 =
* Initial version.

== Upgrade Notice ==