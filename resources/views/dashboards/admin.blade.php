<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Operations</p>
        <h1 class="mt-2 text-2xl font-black">Admin command centre</h1>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Users" :value="$userCount"/>
        <x-stat-card label="Members" :value="$memberCount"/>
        <x-stat-card label="Trainers" :value="$trainerCount"/>
        <x-stat-card label="Pending trainer reviews" :value="$pendingTrainerCount"/>
        <x-stat-card label="Pending therapy leads" :value="$pendingTherapyRequests"/>
        <x-stat-card label="Pending therapy appointments" :value="$pendingTherapyAppointments"/>
        <x-stat-card label="Pending orders" :value="$pendingOrders"/>
        <x-stat-card label="Pending bookings" :value="$pendingBookings"/>
        <x-stat-card label="Products / Services" :value="$productCount.' / '.$serviceCount"/>
    </div>

    <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <x-module-card title="Users and roles" description="Review accounts and assign member, trainer or admin access." :href="route('admin.users.index')" action="Manage users"/>
        <x-module-card title="Trainer applications" description="Approve or reject trainer profiles before public listing." :href="route('admin.trainers.index')" action="Review applications"/>
        <x-module-card title="Membership tiers" description="Edit pricing and manually reassign member tiers." :href="route('admin.memberships.index')" action="Manage tiers"/>
        <x-module-card title="Services" description="Manage Body and Mind service descriptions and availability." :href="route('admin.services.index')" action="Manage services"/>
        <x-module-card title="Other events" description="Create, publish, edit and remove parties, workshops and endurance events." :href="route('admin.events.index')" action="Manage events"/>
        <x-module-card title="Products" description="Manage categories, prices and stock." :href="route('admin.products.index')" action="Manage products"/>
        <x-module-card title="Orders" description="Review guest and member orders and update fulfilment." :href="route('admin.orders.index')" action="Manage orders"/>
        <x-module-card title="Yoga therapy leads" description="Follow up with public and member therapy enquiries." :href="route('admin.therapy.index')" action="Review requests"/>
        <x-module-card title="Therapy appointments" description="Review finder appointments and update their confirmation status." :href="route('admin.therapy-appointments.index')" action="Manage appointments"/>
        <x-module-card title="Trainer bookings" description="See booking activity across the platform." :href="route('admin.bookings.index')" action="View bookings"/>
    </div>
</x-app-layout>
