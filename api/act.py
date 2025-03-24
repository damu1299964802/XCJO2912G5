# app/routes/interaction.py

from flask import Blueprint, jsonify, request,url_for
from flask_login import login_required, current_user
from app.models import db, Like, Favorite, Movie, Music, Game
from sqlalchemy.exc import IntegrityError

bp = Blueprint('interaction', __name__, url_prefix='/api')

def get_media_object(media_type, media_id):
    if media_type == 'movie':
        return Movie.query.get_or_404(media_id)
    elif media_type == 'music':
        return Music.query.get_or_404(media_id)
    elif media_type == 'game':
        return Game.query.get_or_404(media_id)
    return None

@bp.route('/like', methods=['POST'])
@login_required
def toggle_like():
    data = request.get_json()
    media_type = data.get('media_type')
    media_id = data.get('media_id')
    
    # 确保媒体存在
    media = get_media_object(media_type, media_id)
    if not media:
        return jsonify({'error': '内容不存在'}), 404
    
    try:
        # 检查是否已存在点赞
        existing_like = Like.query.filter_by(
            user_id=current_user.id,
            media_type=media_type,
            media_id=media_id
        ).first()
        
        if existing_like:
            db.session.delete(existing_like)
            action = 'unliked'
        else:
            new_like = Like(
                user_id=current_user.id,
                media_type=media_type,
                media_id=media_id
            )
            db.session.add(new_like)
            action = 'liked'
            
        db.session.commit()
        
        # 获取最新的点赞数
        like_count = Like.query.filter_by(
            media_type=media_type,
            media_id=media_id
        ).count()
        
        return jsonify({
            'status': 'success',
            'action': action,
            'like_count': like_count
        })
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@bp.route('/favorite', methods=['POST'])
@login_required
def toggle_favorite():
    data = request.get_json()
    media_type = data.get('media_type')
    media_id = data.get('media_id')
    
    # 确保媒体存在
    media = get_media_object(media_type, media_id)
    if not media:
        return jsonify({'error': '内容不存在'}), 404
    
    try:
        # 检查是否已收藏
        existing_favorite = Favorite.query.filter_by(
            user_id=current_user.id,
            media_type=media_type,
            media_id=media_id
        ).first()
        
        if existing_favorite:
            db.session.delete(existing_favorite)
            action = 'unfavorited'
        else:
            new_favorite = Favorite(
                user_id=current_user.id,
                media_type=media_type,
                media_id=media_id
            )
            db.session.add(new_favorite)
            action = 'favorited'
            
        db.session.commit()
        
        # 获取最新的收藏数
        favorite_count = Favorite.query.filter_by(
            media_type=media_type,
            media_id=media_id
        ).count()
        
        return jsonify({
            'status': 'success',
            'action': action,
            'favorite_count': favorite_count
        })
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@bp.route('/status/<media_type>/<int:media_id>', methods=['GET'])
@login_required
def get_interaction_status(media_type, media_id):
    try:
        is_liked = Like.query.filter_by(
            user_id=current_user.id,
            media_type=media_type,
            media_id=media_id
        ).first() is not None
        
        is_favorited = Favorite.query.filter_by(
            user_id=current_user.id,
            media_type=media_type,
            media_id=media_id
        ).first() is not None
        
        like_count = Like.query.filter_by(
            media_type=media_type,
            media_id=media_id
        ).count()
        
        favorite_count = Favorite.query.filter_by(
            media_type=media_type,
            media_id=media_id
        ).count()
        
        return jsonify({
            'is_liked': is_liked,
            'is_favorited': is_favorited,
            'like_count': like_count,
            'favorite_count': favorite_count
        })
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    
@bp.route('/favorites/<media_type>', methods=['GET'])
@login_required
def get_favorites(media_type):
    try:
        favorites = Favorite.query.filter_by(
            user_id=current_user.id,
            media_type=media_type
        ).all()
        
        items = []
        for fav in favorites:
            media = get_media_object(media_type, fav.media_id)
            if media:
                items.append({
                    'id': media.id,
                    'title': media.title,
                    'cover_url': media.get_cover_url(),
                    'detail_url': url_for(f'main.{media_type}_detail', id=media.id)
                })
        
        return jsonify({
            'status': 'success',
            'items': items
        })
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500