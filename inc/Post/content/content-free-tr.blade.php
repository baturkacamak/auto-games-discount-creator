@if(isset($game) && count($game) > 0)
	<div class="steam-content-body">
		Ucretsiz oyun {{$game['name']}}
	</div>
	<div class="steam-cards">
		<div class="ui cards free-game">
			<div class="ui card">
				@if(isset($game['thumbnail_url']))
					<div class="image">
						<a href="{{$game['url']}}"
						   target="_blank"
						>
							<img src="{{$game['thumbnail_url']}}"
								 alt="{{$game['name']}}"
								 width="100%"
							>
						</a>
					</div>
				@endif
				<div class="content">
					<a class="header"
					   href="{{$game['url']}}"
					   target="_blank"
					>{{$game['name']}}</a>
					<div class="description">
						<div>Fiyatı: <strong>ÜCRETSİZ</strong></div>
					</div>
				</div>
				<div class="extra content">
					<a href="{{$game['url']}}"
					   target="_blank"
					>
						<i class="external icon"></i>
						{{$game['url']}}
					</a>
				</div>
			</div>
		</div>
	</div>
	<div class="ui hidden divider"></div>
@endif
