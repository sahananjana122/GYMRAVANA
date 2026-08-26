<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProgressPhotoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'before_photo' => ['nullable', 'required_without:after_photo', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'after_photo' => ['nullable', 'required_without:before_photo', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $profile = $request->user()->memberProfile()->firstOrCreate([], [
            'joined_at' => today(),
            'status' => 'active',
        ]);
        $newPaths = [];
        $oldPaths = [];

        foreach (['before_photo' => 'before_photo_path', 'after_photo' => 'after_photo_path'] as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $oldPaths[] = $profile->{$column};
            $newPaths[$column] = $request->file($input)->store(
                'members/'.$request->user()->id.'/progress',
                'public',
            );
        }

        try {
            $profile->update($newPaths);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values($newPaths));

            throw $exception;
        }

        Storage::disk('public')->delete(array_values(array_filter($oldPaths)));

        return back()->with('status', 'Your private progress photos were updated.');
    }
}
