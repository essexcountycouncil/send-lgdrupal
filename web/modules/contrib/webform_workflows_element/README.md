# Webform workflows element

This module provides a new element type for Webforms (D8+) that uses the core Workflows functionality to move submissions through a webform.

This means you define the workflow (with workflow type "Webform workflow"), then users with access can use the workflow transitions to modify the workflow status for each submission when editing. You can then track these submissions, trigger handler events, etc based on the workflow progress.

This is the Drupal 8/9 alternative to "webform_workflow" in D7.

# What it does

In detail:

- Results view is filterable by workflow status
- Access controls for the workflow element determine who can use the workflow
- Access controls for the element per transition controls who can do what
- Conditional logic can be used to disable workflow transitions
- Emails can be sent per transition using a handler, including using tokens relating to the workflow change
- User facing and private log messages can be entered when transitioning, and set to be optional/required/hidden per element
- Each transition is logged for the submission, if logs enabled for the webform

# Example

As an example, if you have the following states and transitions:

States:
1. Submitted
2. In process
3. Approved

Transitions:
a) "Start processing" (Submitted -> In process)
b) "Approve" (In process -> Approved)

A user will only be able to move a Submitted form to In process - however they will not be able to skip to Approved as there is no transition set up in the workflow for that. This allows controlling the logic of the workflow (and represents a change from the D7 webform_workflows module which allowed any state to be changed to any state).

# How to use

1. Create your workflow in core Workflows, selecting the "webform workflow" workflow type.
2. Add "webform workflows element" to form.
3. Select the workflow in the element edit page.
4. You can control which log messages are collected for the element - one shown to submitter, one admin only. These log messages can be optional, required or disabled.
5. On 'Access', make sure you select the role or access rules for 'Update' - these are the roles that can edit the workflow for the submission, but it defaults to authenticated user. (Elements default to not appearing on the 'create' webform, i.e. a new webform, but you can change that on Access too by providing an access role or other method).
6. The workflow element is now available on the update form, and in the results view of the form.
7. You should enable logging on the webform.
8. You can check the set-up of your workflow via the "Workflows" tab on the webform. It will summarise the transitions, access rules and e-mails the workflow is set up to have for that form. It is recommended to install the Workflows Diagram module <https://drupal.org/project/workflows_diagram> to show your workflow as a diagram.

## Create an e-mail handler on a workflow change

1. Webform -> Settings -> Emails and handlers
2. Click 'Add workflow transition email"
3. Complete as you would with a normal e-mail handler.
4. On the 'Advanced' tab under 'Additional settings', you can select which transition triggers the e-mail. If you have multiple workflow elements, the name of the element appears in brackets after the transition description.

## Control access to individual transitions

1. Edit element and go to "Access" tab.
2. Scroll down and you will see each transition, e.g. 'Workflow transition "Approve"'
3. Select access accordingly.
4. When a user with access to the element, but without access to the transition, sees the webform element, that transition will be disabled.

## Disable states based on conditional logic

You can disable a transition on the form based on the form values, e.g. disable approval if a field is empty.
1. Go to the 'Conditionals' tab of the element.
2. Add a new conditional.
3. In the "State" dropdown there is a group "Workflow transitions", under which each transition is listed for disabling.
4. Create the rest of the condition as normal and save.

You can provide a message to the user when states are disabled to help them understand why (otherwise they are given a generic message about a conditional disabling the transition):
1. Go to the "General" tab of the element.
2. Find the specific transition under "Webform workflows element settings".
3. Enter message in "Message if disabled by a conditional".

# Contributors

This module contributed by Students' Union UCL (UCLU), continuing our tradition of using webforms and workflows for everything - we also developed the <a href=" https://www.drupal.org/project/webform_workflow">original D7 webform workflows</a> module many years ago.

# Development

## Todo for a stable feature-complete release

- Show workflow summary and public log message for user when they view their submission

- Display logs somehow on workflow bit of form for admin, or at least link to the log

- Make sure multiple workflow elements work on the same form - conditionals are a key issue here currently

## Future plans

- Track user who changed workflow (in log?)

- Allow tracking "on behalf of user" user reference

- Permission / site roles which can override "valid transitions" i.e. select any workflow transition OR permission to do an emergency transition change from the full list.

- Possible separate module: Views filter(s) to find submissions a user could change to a given state for a given webform (i.e. run through any states in the webform element's workflow they'd have permissions to change, then present a list of the forms of that state)

- Transition settings - colour for workflow states
