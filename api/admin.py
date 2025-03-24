# app/routes/admin.py
from flask import Blueprint, render_template, request, jsonify, current_app,flash,redirect,url_for
from flask_login import login_required, current_user
from app.models.media import Movie, Music, Game
from app.models import db
from app.utils.decorators import admin_required
import requests
from datetime import datetime
from bs4 import BeautifulSoup
import spotipy
from spotipy.oauth2 import SpotifyClientCredentials
from app.models.log import AdminLog
import os

bp = Blueprint('admin', __name__, url_prefix='/admin')

def save_cover_image(file, media_type):
    if not file:
        return None
        
    if isinstance(file, bytes):
        filename = f"{media_type}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.jpg"
        with open(os.path.join(current_app.root_path, 'static', 'uploads', 'covers', filename), 'wb') as f:
            f.write(file)
        return filename
        
    filename = f"{media_type}_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{file.filename}"
    file.save(os.path.join(current_app.root_path, 'static', 'uploads', 'covers', filename))
    return filename

def clean_description(html_text):
    if not html_text:
        return ""
    return BeautifulSoup(html_text, 'html.parser').get_text(separator=' ', strip=True)

    
@bp.route('/dashboard')
@login_required
@admin_required
def dashboard():
    movies_count = Movie.query.count()
    music_count = Music.query.count()
    games_count = Game.query.count()
    
    recent_items = {
        'movies': Movie.query.order_by(Movie.created_at.desc()).limit(5).all(),
        'music': Music.query.order_by(Music.created_at.desc()).limit(5).all(),
        'games': Game.query.order_by(Game.created_at.desc()).limit(5).all()
    }
    
    return render_template('dashboard.html', 
                         counts={'movies': movies_count, 'music': music_count, 'games': games_count},
                         recent_items=recent_items)

def get_imdb_data(imdb_id):
    url = f"http://www.omdbapi.com/?i={imdb_id}&apikey={current_app.config['OMDB_API_KEY']}"
    try:
        response = requests.get(url, timeout=10)
        if response.ok:
            return response.json()
        return None
    except Exception as e:
        current_app.logger.error(f"OMDB API error: {str(e)}")
        return None

@bp.route('/movie/add', methods=['GET', 'POST'])
@login_required
@admin_required
def add_movie():
    if request.method == 'POST':
        try:
            imdb_id = request.form.get('imdb_id')
            if not imdb_id:
                flash('请输入IMDB ID', 'error')
                return redirect(url_for('admin.add_movie'))
                
            movie_data = get_imdb_data(imdb_id)
            if not movie_data:
                flash('无法从IMDB获取电影信息', 'error')
                return redirect(url_for('admin.add_movie'))

            # 处理日期，某些电影可能没有具体发布日期
            try:
                release_date = datetime.strptime(movie_data.get('Released', ''), '%d %b %Y')
            except ValueError:
                release_date = datetime.strptime(movie_data.get('Year', '2000'), '%Y')

            # 处理时长，去掉 "min" 并转换为整数
            duration = movie_data.get('Runtime', '0 min').split()[0]
            try:
                duration = int(duration)
            except ValueError:
                duration = 0

            movie = Movie(
                title=movie_data.get('Title', 'Unknown'),
                description=movie_data.get('Plot', ''),
                release_date=release_date,
                duration=duration,
                director=movie_data.get('Director', ''),
                genre=movie_data.get('Genre', ''),
                imdb_id=imdb_id,
                rating=float(movie_data.get('imdbRating', 0)),
                box_office=movie_data.get('BoxOffice', 'N/A'), 
                user_id=current_user.id
            )
            
            # 获取海报
            if movie_data.get('Poster') and movie_data['Poster'] != 'N/A':
                try:
                    poster_response = requests.get(movie_data['Poster'], timeout=10)
                    if poster_response.ok:
                        filename = save_cover_image(poster_response.content, 'movie')
                        movie.cover_image = filename
                except Exception as e:
                    current_app.logger.error(f"Poster download error: {str(e)}")
            
            db.session.add(movie)
            log = AdminLog(
                admin_id=current_user.id,
                action='添加',
                target_type='movie',
                target_id=movie.id,
                target_name=movie.title,
                details=f'管理员{current_user.username}添加了电影 {movie.title}'
            )
            db.session.add(log)
            db.session.commit()
            flash('电影添加成功！', 'success')
            return redirect(url_for('admin.dashboard'))
            
        except Exception as e:
            db.session.rollback()
            current_app.logger.error(f"Movie addition error: {str(e)}")
            flash(f'添加电影失败：{str(e)}', 'error')
            return redirect(url_for('admin.add_movie'))
            
    return render_template('admin/movie_form.html')

def get_spotify_data(spotify_id):
    try:
        sp = spotipy.Spotify(auth_manager=SpotifyClientCredentials(
            client_id=current_app.config['SPOTIFY_CLIENT_ID'],
            client_secret=current_app.config['SPOTIFY_CLIENT_SECRET']
        ))
        track = sp.track(spotify_id)
        
        return {
            'title': track['name'],
            'artist': ', '.join([artist['name'] for artist in track['artists']]),
            'album': track['album']['name'],
            'duration': track['duration_ms'] // 1000,  # 转换为秒
            'release_date': track['album']['release_date'],
            'cover_url': track['album']['images'][0]['url'] if track['album']['images'] else None
        }
    except Exception as e:
        current_app.logger.error(f"Spotify API error: {str(e)}")
        return None

@bp.route('/music/add', methods=['GET', 'POST'])
@login_required
@admin_required
def add_music():
    if request.method == 'POST':
        try:
            spotify_id = request.form.get('spotify_id')
            if not spotify_id:
                flash('请输入Spotify ID', 'error')
                return redirect(url_for('admin.add_music'))

            # 检查是否已存在
            if Music.query.filter_by(spotify_id=spotify_id).first():
                flash('该音乐已存在', 'warning')
                return redirect(url_for('admin.add_music'))
                
            music_data = get_spotify_data(spotify_id)
            if not music_data:
                flash('获取音乐信息失败', 'error')
                return redirect(url_for('admin.add_music'))

            music = Music(
                title=music_data['title'],
                artist=music_data['artist'],
                album=music_data['album'],
                description=f"{music_data['title']} - {music_data['artist']} - {music_data['album']}",
                release_date=datetime.strptime(music_data['release_date'], '%Y-%m-%d'),
                duration=music_data['duration'],
                spotify_id=spotify_id,
                user_id=current_user.id
            )
            
            # 获取封面图片
            if music_data['cover_url']:
                try:
                    cover_response = requests.get(music_data['cover_url'], timeout=10)
                    if cover_response.ok:
                        filename = save_cover_image(cover_response.content, 'music')
                        music.cover_image = filename
                except Exception as e:
                    current_app.logger.error(f"Cover download error: {str(e)}")
            
            db.session.add(music)
            log = AdminLog(
                admin_id=current_user.id,
                action='添加',
                target_type='music',
                target_id=music.id,
                target_name=music.title,
                details=f'管理员{current_user.username}添加了音乐 {music.title}'
            )
            db.session.add(log)
            db.session.commit()
            flash('音乐添加成功！', 'success')
            return redirect(url_for('admin.dashboard'))
            
        except Exception as e:
            db.session.rollback()
            current_app.logger.error(f"Music addition error: {str(e)}")
            flash(f'添加音乐失败：{str(e)}', 'error')
            
    return render_template('admin/music_form.html')

def parse_steam_date(steam_data):
    date_str = steam_data.get('release_date', {}).get('date')
    try:
        return datetime.strptime(date_str, '%d %b, %Y') 
    except ValueError:
        try:
            return datetime.strptime(date_str, '%Y-%m-%d')
        except ValueError:
            return datetime.now()
def get_steam_game_data(steam_id):
    try:
        params = {
            'key': current_app.config['STEAM_API_KEY'],
            'appids': steam_id,
            'cc': 'cn',
            'l': 'zh'
        }
        headers = {
            'Origin': current_app.config['STEAM_DOMAIN'],
            'Referer': f"{current_app.config['STEAM_DOMAIN']}/app/{steam_id}",
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        
        response = requests.get(
            "https://store.steampowered.com/api/appdetails",
            params=params,
            headers=headers,
            timeout=10
        )
        
        if response.ok and response.json()[steam_id]['success']:
            return response.json()[steam_id]['data']
        return None
        
    except Exception as e:
        current_app.logger.error(f"Steam API error: {str(e)}")
        return None

@bp.route('/game/add', methods=['GET', 'POST'])
@login_required
@admin_required
def add_game():
    if request.method == 'POST':
        steam_id = request.form.get('steam_id')
        if not steam_id:
            flash('请输入Steam ID', 'error')
            return redirect(url_for('admin.add_game'))
            
        steam_data = get_steam_game_data(steam_id)
        if not steam_data:
            flash('获取游戏信息失败', 'error')
            return redirect(url_for('admin.add_game'))
            
        game_data = {
            'title': steam_data['name'],
            'description': clean_description(steam_data.get('detailed_description')),
            'release_date': parse_steam_date(steam_data),
            'developer': ", ".join(steam_data.get('developers', [])),
            'publisher': ", ".join(steam_data.get('publishers', [])),
            'platform': 'PC',
            'genre': ", ".join(g['description'] for g in steam_data.get('genres', [])),
            'steam_id': steam_id,
            'steam_rating': steam_data.get('metacritic', {}).get('score'),
            'user_id': current_user.id
        }
        
        # 获取封面图片
        if steam_data.get('header_image'):
            img_response = requests.get(steam_data['header_image'], timeout=10)
            if img_response.ok:
                filename = save_cover_image(img_response.content, 'game')
                game_data['cover_image'] = filename
                
        game = Game(**game_data)
        db.session.add(game)
        log = AdminLog(
                admin_id=current_user.id,
                action='添加',
                target_type='game',
                target_id=game.id,
                target_name=game.title,
                details=f'管理员{current_user.username}添加了游戏 {game.title}'
            )
        db.session.add(log)
        db.session.commit()
        flash('游戏添加成功！', 'success')
        return redirect(url_for('admin.dashboard'))
        
    return render_template('admin/game_form.html')

@bp.route('/<media_type>/<int:id>/delete', methods=['POST'])
@login_required
@admin_required
def delete_item(media_type, id):
    try:
        model_map = {
            'movie': Movie,
            'music': Music,
            'game': Game
        }
        
        model = model_map.get(media_type)
        if not model:
            flash('无效的媒体类型', 'error')
            return redirect(url_for('admin.dashboard'))
            
        item = model.query.get(id)
        if not item:
            flash('项目不存在', 'error')
            return redirect(url_for('admin.dashboard'))

        # 记录日志
        log = AdminLog(
            admin_id=current_user.id,
            action='删除',
            target_type=media_type,
            target_id=id,
            target_name=item.title,
            details=f'管理员{current_user.username}删除了{media_type} {item.title}'
        )
        db.session.add(log)

        # 删除操作
        if item.cover_image:
            try:
                cover_path = os.path.join(current_app.root_path, 'static', 'uploads', 'covers', item.cover_image)
                if os.path.exists(cover_path):
                    os.remove(cover_path)
            except Exception as e:
                current_app.logger.error(f"Cover image deletion failed: {str(e)}")

        db.session.delete(item)
        db.session.commit()
        
        flash(f'{media_type}删除成功！', 'success')
        return redirect(url_for('admin.dashboard'))
        
    except Exception as e:
        db.session.rollback()
        current_app.logger.error(f"Delete failed: {str(e)}")
        flash('删除失败，请重试', 'error')
        return redirect(url_for('admin.dashboard'))

# 添加日志相关路由
@bp.route('/logs')
@login_required  # 所有用户都可以查看
def view_logs():
    page = request.args.get('page', 1, type=int)
    per_page = 20
    
    logs = AdminLog.query.order_by(AdminLog.created_at.desc()).paginate(
        page=page, per_page=per_page)
    
    return render_template('admin/logs.html', logs=logs)