$destDir = 'C:\Users\ctc\.gemini\antigravity-ide\scratch\Flat-Management-System\images'

$standardPhotos = @(
    @{ name = 'std_gated_community.jpg'; url = 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_apartment_building.jpg'; url = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_midrise_block.jpg'; url = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_balcony_view.jpg'; url = 'https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_living_2bhk.jpg'; url = 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_living_3bhk.jpg'; url = 'https://images.unsplash.com/photo-1554995207-c18c203602cb?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_compact_1bhk.jpg'; url = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_master_bedroom.jpg'; url = 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_guest_bedroom.jpg'; url = 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_modular_kitchen.jpg'; url = 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_compact_kitchen.jpg'; url = 'https://images.unsplash.com/photo-1507089947368-19c1da9775ae?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_dining_room.jpg'; url = 'https://images.unsplash.com/photo-1617806118233-18e1de247200?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_modern_bathroom.jpg'; url = 'https://images.unsplash.com/photo-1620626011761-996317b8d101?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_kids_play_area.jpg'; url = 'https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_society_pool.jpg'; url = 'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_society_gym.jpg'; url = 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_community_hall.jpg'; url = 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1200&q=80' },
    @{ name = 'std_security_gate.jpg'; url = 'https://images.unsplash.com/photo-1577495508048-b635879837f1?auto=format&fit=crop&w=1200&q=80' }
)

foreach ($item in $standardPhotos) {
    $name = $item.name
    $url = $item.url
    $dest = "$destDir\$name"
    Write-Host "Downloading $name..."
    try {
        Invoke-WebRequest -Uri $url -OutFile $dest -UserAgent "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" -TimeoutSec 20
        Write-Host "Saved $name"
    } catch {
        Write-Host "Error downloading $name : $_"
    }
}

Write-Host "Standard residential photos download complete!"
