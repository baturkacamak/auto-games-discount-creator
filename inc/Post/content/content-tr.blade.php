@if(isset($games) && count($games) > 0)
	<div class="steam-content-body">
		Bugün alınmaya değer toplam {{ count($games) }} oyun var.
	</div>
	<div class="steam-cards">
		<div class="ui cards">
			@foreach($games as $game)
				<div class="ui card">
					<div class="image">
						<a href="{{ $game['url'] }}"
						   target="_blank"
						>
							<img src="{{ $game['thumbnail_url'] }}">
						</a>
					</div>
					<div class="content">
						<a class="header"
						   href="{{ $game['url'] }}"
						   target="_blank"
						>{{ $game['name'] }}</a>
						<div class="description">
							<div>Fiyatı: <strong>{{ $game['price'] }}</strong> TL</div>
							<div>İndirim Oranı: <strong>{{ $game['cut'] }}</strong>%</div>
						</div>
					</div>
					<div class="extra content">
						<a href="{{ $game['url'] }}"
						   target="_blank"
						>
							<i class="steam icon"></i>
							{{ $game['url'] }}
						</a>
					</div>
				</div>
			@endforeach
		</div>
	</div>
@endif
