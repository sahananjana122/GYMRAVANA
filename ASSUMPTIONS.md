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
14. Therapy specialists are stored separately from trainer user accounts because a specialist may provide consultations without using the trainer dashboard.
15. The current MVP stores preferred appointment times but does not enforce real-time calendar availability or prevent schedule conflicts; administrators confirm requests manually.
16. Group-program registrations support both signed-in members and guests so the future public "Join Class" action can use the same database workflow.
