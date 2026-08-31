{{-- Shared bottom tab bar — set $active to one of: home, browse, profile --}}
<bottom-nav>
    <bottom-nav-item id="home"    icon="home"          url="/"        label="Home"    :active="($active ?? '') === 'home'" />
    <bottom-nav-item id="browse"  icon="grid_view"     url="/browse"  label="Browse"  :active="($active ?? '') === 'browse'" />
    <bottom-nav-item id="profile" icon="person"        url="/profile" label="Profile" :active="($active ?? '') === 'profile'" />
</bottom-nav>
