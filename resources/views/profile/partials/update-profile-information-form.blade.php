<section>
    <header>
        <h2 class="text-lg font-medium text-zinc-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-zinc-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-zinc-300">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-zinc-400 hover:text-red-400 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if ($user->hasRole('member'))
            <div>
                <x-input-label for="phone" value="Mobile number (optional)" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->memberProfile?->phone)" autocomplete="tel" placeholder="+94 77 123 4567" />
                <p class="mt-1 text-xs text-zinc-500">Used only to prepare a WhatsApp click-to-chat reminder link. GymRAVANA does not send WhatsApp messages automatically.</p>
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[.025] p-5">
                <input type="hidden" name="share_measurements_with_trainer" value="0">
                <label for="share_measurements_with_trainer" class="flex cursor-pointer items-start gap-3">
                    <input id="share_measurements_with_trainer" name="share_measurements_with_trainer" type="checkbox" value="1" class="mt-1 rounded border-white/20 bg-black text-lime-400 focus:ring-lime-400" @checked(old('share_measurements_with_trainer', $user->memberProfile?->share_measurements_with_trainer))>
                    <span>
                        <strong class="block text-sm text-stone-200">Share measurement trends with my assigned trainers</strong>
                        <span class="mt-1 block text-xs leading-5 text-stone-500">When enabled, trainers connected through an accepted or completed booking can view monthly weight and waist changes. Your raw notes remain private and nothing is published.</span>
                    </span>
                </label>
                <x-input-error class="mt-2" :messages="$errors->get('share_measurements_with_trainer')" />
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-zinc-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
