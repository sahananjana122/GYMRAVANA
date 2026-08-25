# GymRAVANA build assumptions

1. Guest checkout is enabled. Orders created by guests keep a nullable `user_id` and collect the customer details required for fulfilment.
2. Checkout uses a mock payment step for the undergraduate MVP. It records an order with `pending` status and does not contact a payment gateway.
3. Yoga therapy is a lead-capture workflow, not medical diagnosis or a real-time appointment calendar. One therapy category is selected per request.
4. Email verification remains optional during development. Authentication and role authorization still protect private areas.
5. The existing Spatie roles system remains the source of truth instead of adding a second `role` column to `users`.
6. Existing workout, wellness, measurement, and therapy data is preserved. The old therapy status `pending` is displayed as “New” for compatibility.
7. Trainer applications receive the `trainer` role immediately but remain hidden from the public directory until an administrator changes the profile status to `approved`.
8. Product checkout does not reserve stock while items sit in a session cart. Stock is checked and reduced only when an order is submitted.
9. Prices use Sri Lankan rupees (LKR) because the project is being developed in Sri Lanka.
10. Promotions and FAQ content are presentation content in v1 and are maintained in the home controller rather than separate database tables.
11. The previous `master` role is retained in existing database records for compatibility but is not part of the new site map or registration/admin assignment options.
12. Seeded trainer and product imagery is optional; the interface provides branded gradient/initial fallbacks when no uploaded image exists.
13. Condition-to-treatment recommendations are educational routing suggestions, not diagnoses. Urgent, severe, or unexplained symptoms must be directed to appropriate medical care rather than this booking flow.
14. Therapy specialists remain separate professional profile records, but each may now be linked to one nullable authenticated user account with the dedicated `therapist` role.
15. A preferred therapy appointment time is only a client request. A linked therapist or administrator sets the confirmed time, duration and arrival time, and confirmed appointments may not overlap for the same therapist or client.
16. Group-program registrations support both signed-in members and guests so the future public "Join Class" action can use the same database workflow.
17. Only completed product orders currently contain both a real amount and a completion state, so they are the only automatic finance source in Phase 2. Other income is recorded manually after payment is confirmed.
18. Cancelling or otherwise reversing a completed order voids its linked ledger entry instead of deleting it. Re-completion reactivates the same entry so revenue cannot be counted twice.
19. Manual finance records are also voided rather than physically deleted, preserving a basic undergraduate-level audit trail while excluding them from reports.
20. A requested trainer time is the member's preference, not a confirmed appointment. Only the trainer or administrator can accept it and set the authoritative session time, duration and arrival time.
21. Accepted trainer sessions may not overlap another accepted session belonging to the same trainer or member. Declined and cancelled records remain available as booking history.
22. Older accepted bookings without Phase 3 scheduling fields are preserved and remain visible for manual completion by a trainer or administrator.
23. Session notifications use Laravel's database and mail channels. During assignment development, `MAIL_MAILER=log` is the intentional email fallback; real SMTP is deferred to client deployment.
24. WhatsApp integration is limited to a `wa.me` click-to-chat link with a prepared reminder. The application does not call a WhatsApp provider or record a WhatsApp message as delivered.
25. Manual session reminders have a five-minute per-session cooldown and retain a reminder count and last-sent timestamp for basic accountability.
26. The original `workout_plans` table remains the member's self-service workout library. Trainer-to-member workout and meal assignments use separate normalized `member_plans` and `member_plan_items` records so public workouts are not falsely presented as assigned plans.
27. Non-draft assigned plans are read-only to the owning member. Phase 6 trainer actions create structured items and preserve earlier records as archived versions instead of overwriting them.
28. Monthly member progress is calculated from the signed-in member's current-month workout/wellness completions, completed sessions, points and private measurements. The weight trend is informational and is not presented as medical advice.
29. The Google Drive library URL is optional configuration stored in one environment-backed config value. Only valid HTTP/HTTPS links are rendered, and Google Drive permissions remain authoritative.
30. A member is treated as assigned to a trainer after an accepted or completed booking connects that member and trainer profile. Pending, declined and cancelled requests do not grant plan or tracker access.
31. Only one active plan per trainer, member and plan type is retained; activating a replacement archives that trainer's previous active plan. A different legitimately assigned trainer cannot modify another trainer's records.
32. Monthly tracker consistency is deterministic: distinct days containing a workout, wellness activity or completed session are divided by elapsed days for the current month, or total days for an earlier month. This percentage is descriptive, not AI.
33. Member body-measurement sharing is off by default and may be changed by the member from their profile. Assigned trainers receive only monthly weight/waist change summaries when permission is enabled; measurement notes stay private.
34. Monthly trainer notes and assessments are private operational records available to the owning trainer and administrators. They are not automatically published to the Notice Board or treated as medical diagnosis.
