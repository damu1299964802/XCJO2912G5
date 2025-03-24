# app/routes/main.py
from flask import Blueprint, render_template, request, redirect, url_for, flash, current_app,abort
from flask_login import login_required, current_user
from app.utils.upload import save_avatar
from app.models import db, Movie, Music, Game
from flask import jsonify
import os

bp = Blueprint('main', __name__)

@bp.route('/')
def index():
    # 获取每个类别的最新项目
    latest_movies = Movie.query.order_by(Movie.created_at.desc()).limit(3).all()
    latest_music = Music.query.order_by(Music.created_at.desc()).limit(3).all()
    latest_games = Game.query.order_by(Game.created_at.desc()).limit(3).all()
    
    return render_template('index.html',
                         movies=latest_movies,
                         music=latest_music,
                         games=latest_games)

# 详情页路由
@bp.route('/movie/<int:id>')
def movie_detail(id):
    movie = Movie.query.get_or_404(id)
    return render_template('details/movie.html', movie=movie)

@bp.route('/music/<int:id>')
def music_detail(id):
    music = Music.query.get_or_404(id)
    return render_template('details/music.html', music=music)

@bp.route('/game/<int:id>')
def game_detail(id):
    game = Game.query.get_or_404(id)
    return render_template('details/game.html', game=game)


@bp.route('/dashboard')
@login_required
def dashboard():
    # 获取媒体数量统计
    counts = {
        'movies': Movie.query.count(),
        'music': Music.query.count(),
        'games': Game.query.count()
    }
    
    # 获取最近添加的项目
    recent_items = {
        'movies': Movie.query.order_by(Movie.created_at.desc()).limit(3).all(),
        'music': Music.query.order_by(Music.created_at.desc()).limit(3).all(),
        'games': Game.query.order_by(Game.created_at.desc()).limit(3).all()
    }
    
    return render_template('dashboard.html', 
                         counts=counts,
                         recent_items=recent_items)

@bp.route('/update_avatar', methods=['POST'])
@login_required
def update_avatar():
    if 'avatar' not in request.files:
        flash('没有选择文件', 'error')
        return redirect(url_for('main.dashboard'))
    
    file = request.files['avatar']
    if file.filename == '':
        flash('没有选择文件', 'error')
        return redirect(url_for('main.dashboard'))

    filename = save_avatar(file, current_user.id)
    if filename:
        # 删除旧头像（如果不是默认头像）
        if current_user.avatar_path != 'default.jpg':
            old_avatar = os.path.join(current_app.root_path, 'static/uploads/avatars', current_user.avatar_path)
            if os.path.exists(old_avatar):
                os.remove(old_avatar)
        
        current_user.avatar_path = filename
        db.session.commit()
        flash('头像更新成功！', 'success')
    else:
        flash('头像上传失败，请确保上传了有效的图片文件（小于2MB）', 'error')
    
    return redirect(url_for('main.dashboard'))

# 在 main.py 中添加搜索路由
@bp.route('/search')
def search():
    query = request.args.get('q', '')
    media_type = request.args.get('type', 'all')
    
    if not query:
        return jsonify([])
        
    results = []
    
    if media_type in ['all', 'movie']:
        movies = Movie.query.filter(
            (Movie.title.ilike(f'%{query}%')) |
            (Movie.director.ilike(f'%{query}%')) |
            (Movie.description.ilike(f'%{query}%'))
        ).limit(5).all()
        results.extend([{
            'id': movie.id,
            'title': movie.title,
            'type': 'movie',
            'cover': movie.get_cover_url(),
            'detail_url': url_for('main.movie_detail', id=movie.id),
            'subtitle': f'导演: {movie.director}' if movie.director else None
        } for movie in movies])
    
    if media_type in ['all', 'music']:
        music_items = Music.query.filter(
            (Music.title.ilike(f'%{query}%')) |
            (Music.artist.ilike(f'%{query}%')) |
            (Music.album.ilike(f'%{query}%'))
        ).limit(5).all()
        results.extend([{
            'id': music.id,
            'title': music.title,
            'type': 'music',
            'cover': music.get_cover_url(),
            'detail_url': url_for('main.music_detail', id=music.id),
            'subtitle': f'艺术家: {music.artist}' if music.artist else None
        } for music in music_items])
    
    if media_type in ['all', 'game']:
        games = Game.query.filter(
            (Game.title.ilike(f'%{query}%')) |
            (Game.developer.ilike(f'%{query}%')) |
            (Game.publisher.ilike(f'%{query}%')) |
            (Game.description.ilike(f'%{query}%'))
        ).limit(5).all()
        results.extend([{
            'id': game.id,
            'title': game.title,
            'type': 'game',
            'cover': game.get_cover_url(),
            'detail_url': url_for('main.game_detail', id=game.id),
            'subtitle': f'开发商: {game.developer}' if game.developer else None
        } for game in games])
        
    return jsonify(results)

@bp.route('/all/<media_type>')
def all_media(media_type):
    page = request.args.get('page', 1, type=int)
    per_page = 12  # 每页显示的数量
    
    if media_type == 'movies':
        items = Movie.query.order_by(Movie.created_at.desc()).paginate(page=page, per_page=per_page)
        title = "所有电影"
        template = 'lists/movie_list.html'
    elif media_type == 'music':
        items = Music.query.order_by(Music.created_at.desc()).paginate(page=page, per_page=per_page)
        title = "所有音乐"
        template = 'lists/music_list.html'
    elif media_type == 'games':
        items = Game.query.order_by(Game.created_at.desc()).paginate(page=page, per_page=per_page)
        title = "所有游戏"
        template = 'lists/games_list.html'
    else:
        abort(404)
    
    return render_template(template, items=items, title=title)

