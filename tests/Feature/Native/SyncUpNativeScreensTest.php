<?php

use App\NativeComponents\SyncUpNative\SyncUpNativeChats;
use App\NativeComponents\SyncUpNative\SyncUpNativeLogin;
use App\NativeComponents\SyncUpNative\SyncUpNativeProfile;
use Native\Mobile\Testing\Native;

it('shows the chats list inside the SyncUp tab bar', function () {
    Native::visit('/syncup-native')
        ->assertScreen(SyncUpNativeChats::class)
        ->assertHasTabBar()
        ->assertHasTab('Messages')
        ->assertTabActive('Messages');
});

it('switches the active conversation filter', function () {
    Native::visit('/syncup-native')
        ->assertSet('activeFilter', 'All')
        ->call('setFilter', 'Unread')
        ->assertSet('activeFilter', 'Unread');
});

it('navigates to a chat thread from the list', function () {
    Native::visit('/syncup-native')
        ->assertNoNavigation()
        ->call('openChat', 2)
        ->assertNavigatedTo('/syncup-native/chat/2');
});

it('opens and closes the new-message sheet', function () {
    Native::visit('/syncup-native')
        ->call('newMessage')
        ->assertSet('showNewMessage', true)
        ->call('closeNewMessage')
        ->assertSet('showNewMessage', false);
});

it('starting a chat with a friend navigates and closes the sheet', function () {
    Native::visit('/syncup-native')
        ->call('newMessage')
        ->call('startChatWith', 3)
        ->assertNavigatedTo('/syncup-native/chat/3');
});

it('logs in by replacing to the chats screen', function () {
    Native::visit('/syncup-native/login')
        ->assertScreen(SyncUpNativeLogin::class)
        ->call('updateEmail', 'shane@example.com')
        ->call('updatePassword', 'secret')
        ->call('login')
        ->assertReplacedWith('/syncup-native');
});

it('toggles password visibility on the login screen', function () {
    Native::visit('/syncup-native/login')
        ->assertSet('showPassword', false)
        ->call('toggleVisibility')
        ->assertSet('showPassword', true);
});

it('marks the profile dirty on edit and saved on save', function () {
    Native::visit('/syncup-native/profile')
        ->assertScreen(SyncUpNativeProfile::class)
        ->call('saveProfile')
        ->assertSet('saved', true)
        ->call('updateName', 'New Name')
        ->assertSet('saved', false)
        ->call('saveProfile')
        ->assertSet('saved', true);
});

it('signs out to the login screen', function () {
    Native::visit('/syncup-native/profile')
        ->call('signOut')
        ->assertNavigatedTo('/syncup-native/login');
});
