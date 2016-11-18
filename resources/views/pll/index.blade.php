@extends('layout.app')
@section('head')
    Home <small>Create playlists</small>
@endsection

@section('content')
    @if (empty($authUrl))
    <form method="post" action="pll/createPll">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"> Danh sách từ khóa </h3>
                </div>
                <div class="panel-body">
                        <div class="form-group">
                            <textarea class="form-control" rows="7" name="txtKeyWord" placeholder="Mỗi từ khóa trên 1 dòng"></textarea>
                        </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"> Danh sách video của bạn </h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <textarea class="form-control" rows="10" id="txtMyVideo" name="txtMyVideo" placeholder="Mỗi video trên 1 dòng"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"> Thông tin playlist</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>Số lượng pll tối đa / 1 từ khóa (MAX 10 THÔI NHÉ)</label>
                        <input class="form-control" max="100" value="10" type="number" name="numberPll">
                    </div>

                    <div class="form-group">
                        <label>Số video tìm được / pll</label>
                        <input class="form-control" max="100" value="10" type="number" name="numberVideo">
                    </div>

                    <div class="form-group">
                        <label>Số video của bạn / pll</label>
                        <input class="form-control" max="100" value="10" type="number" name="numberMyVideo">
                    </div>

                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">Bắt đầu tạo !!</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"> Log</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        @if (session('html'))
                            <div class="alert alert-success">
                                @if (is_array(session('html')))
                                    PLL đã tạo: <br/>
                                    @foreach (session('html') as $html)
                                        - <a target="_blank" href="https://www.youtube.com/playlist?list={{ $html }}">{{ $html }}</a><br/>
                                    @endforeach
                                @else
                                    {{ session('html') }}
                                @endif

                            </div>
                        @else
                            <div class="alert alert-success">
                                Bạn chưa tạo pll nào !
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </form>
    @else
        <div class="text-center">
            <a class="btn btn-primary" href="{{ $authUrl }}"> Trước tiên bạn cần đăng nhập !!</a>
        </div>
    @endif

@endsection