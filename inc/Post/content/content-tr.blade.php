@if(isset($games) && count($games) > 0)
	<div class="steam-content-body">
		{{ sprintf($copy['discount_intro'] ?? 'There are %d games worth grabbing today.', count($games)) }}
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
							<div>{{ $copy['price_label'] ?? 'Price' }}: <strong>{{ $game['price'] }}</strong> {{ $game['currency_code'] ?? '' }}</div>
							<div>{{ $copy['discount_label'] ?? 'Discount' }}: <strong>{{ $game['cut'] }}</strong>%</div>
							<div>{{ $copy['store_label'] ?? 'Store' }}: <strong>{{ strtoupper($game['store_key'] ?? '') }}</strong></div>
						</div>
					</div>
					<div class="extra content">
						<a href="{{ $game['url'] }}"
						   target="_blank"
						>
							<i class="external icon"></i>
							{{ $game['url'] }}
						</a>
					</div>
				</div>
			@endforeach
		</div>
	</div>
@endif
