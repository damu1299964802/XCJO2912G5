# app/models/user.py
from app import db
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime
import os
from flask import url_for, current_app

class User(UserMixin, db.Model):
    __tablename__ = 'user'
    
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(128), nullable=False)
    avatar_path = db.Column(db.String(200), nullable=False, default='default.jpg')
    created_at = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    is_admin = db.Column(db.Boolean, default=False)
    likes = db.relationship('Like', backref='user', lazy='dynamic')
    favorites = db.relationship('Favorite', backref='user', lazy='dynamic')
    
    def __init__(self, username, email):
        self.username = username
        self.email = email

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    def get_avatar_url(self):
        if not self.avatar_path:
            return url_for('static', filename='uploads/default.jpg')
            
        avatar_full_path = os.path.join(
            current_app.root_path, 
            'static', 
            'uploads', 
            'avatars', 
            self.avatar_path
        )
        
        if not os.path.exists(avatar_full_path):
            return url_for('static', filename='uploads/default.jpg')
            
        return url_for('static', filename=f'uploads/avatars/{self.avatar_path}')
    def get_interaction_stats(self):
        """获取用户的交互统计信息"""
        return {
            'likes': {
                'total': self.likes.count(),
                'movies': self.likes.filter_by(media_type='movie').count(),
                'music': self.likes.filter_by(media_type='music').count(),
                'games': self.likes.filter_by(media_type='game').count()
            },
            'favorites': {
                'total': self.favorites.count(),
                'movies': self.favorites.filter_by(media_type='movie').count(),
                'music': self.favorites.filter_by(media_type='music').count(),
                'games': self.favorites.filter_by(media_type='game').count()
            }
        }

    def get_favorites_by_type(self, media_type):
        """获取指定类型的收藏内容"""
        from app.models.media import Movie, Music, Game
        
        favorites = self.favorites.filter_by(media_type=media_type).all()
        items = []
        
        for fav in favorites:
            if media_type == 'movie':
                item = Movie.query.get(fav.media_id)
            elif media_type == 'music':
                item = Music.query.get(fav.media_id)
            elif media_type == 'game':
                item = Game.query.get(fav.media_id)
                
            if item:
                items.append(item)
                
        return items

    def has_liked(self, media_type, media_id):
        """检查用户是否点赞了指定内容"""
        return self.likes.filter_by(
            media_type=media_type,
            media_id=media_id
        ).first() is not None

    def has_favorited(self, media_type, media_id):
        """检查用户是否收藏了指定内容"""
        return self.favorites.filter_by(
            media_type=media_type,
            media_id=media_id
        ).first() is not None

    def __repr__(self):
        return f'<User {self.username}>'