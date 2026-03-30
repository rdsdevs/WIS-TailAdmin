@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Mi perfil" />
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-7">Perfil</h3>
        <x-profile.profile-card :user="$user" />
        <x-profile.personal-info-card :user="$user" />
        <x-profile.address-card :user="$user" />
        <livewire:profile.change-password />
    </div>
@endsection
