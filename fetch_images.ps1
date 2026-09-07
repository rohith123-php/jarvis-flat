$brainDir = 'C:\Users\ctc\.gemini\antigravity-ide\brain\d5a206fa-bac6-4f3a-913e-b846a3539a2e'
$destDir = 'C:\Users\ctc\.gemini\antigravity-ide\scratch\Flat-Management-System\images'

# 1. Copy 8 generated AI images
Copy-Item "$brainDir\grand_waterfront_estate_1788710704545.jpg" "$destDir\grand_waterfront_estate.jpg" -Force
Copy-Item "$brainDir\royal_palace_villa_1788710776447.jpg" "$destDir\royal_palace_villa.jpg" -Force
Copy-Item "$brainDir\futuristic_glass_tower_1788710867041.jpg" "$destDir\futuristic_glass_tower.jpg" -Force
Copy-Item "$brainDir\private_island_villa_1788710957535.jpg" "$destDir\private_island_villa.jpg" -Force
Copy-Item "$brainDir\evening_mansion_facade_1788711067813.jpg" "$destDir\evening_mansion_facade.jpg" -Force
Copy-Item "$brainDir\zen_courtyard_villa_1788711094595.jpg" "$destDir\zen_courtyard_villa.jpg" -Force
Copy-Item "$brainDir\sky_bridge_residences_1788711354417.jpg" "$destDir\sky_bridge_residences.jpg" -Force
Copy-Item "$brainDir\hillside_luxury_retreat_1788711384394.jpg" "$destDir\hillside_luxury_retreat.jpg" -Force

# 2. Curated Grand Photos for remaining 12
$downloads = @(
    @{ name = 'double_height_grand_hall.jpg'; url = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'presidential_master_suite.jpg'; url = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'chef_gourmet_kitchen.jpg'; url = 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'spa_marble_bathroom.jpg'; url = 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'private_home_cinema.jpg'; url = 'https://images.unsplash.com/photo-1595769816263-9b910be24d5f?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'executive_penthouse_study.jpg'; url = 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'rooftop_infinity_skypool.jpg'; url = 'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'private_indoor_pool_spa.jpg'; url = 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'rooftop_helipad_lounge.jpg'; url = 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'championship_tennis_court.jpg'; url = 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'luxury_wine_tasting_lounge.jpg'; url = 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&w=1600&q=85' },
    @{ name = 'private_botanical_garden.jpg'; url = 'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?auto=format&fit=crop&w=1600&q=85' }
)

foreach ($item in $downloads) {
    $name = $item.name
    $url = $item.url
    $dest = "$destDir\$name"
    Write-Host "Downloading $name..."
    try {
        Invoke-WebRequest -Uri $url -OutFile $dest -UserAgent "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" -TimeoutSec 30
        Write-Host "Successfully saved $name"
    } catch {
        Write-Host "Failed to download $name : $_"
    }
}

Write-Host "All done!"
