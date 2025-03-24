# app/models/media.py
from app import db
from datetime import datetime
from flask import url_for

def get_cover_url(instance, default_image):
    """获取封面图片 URL"""
    if instance.cover_image:
        return url_for('static', filename=f'uploads/covers/{instance.cover_image}')
    return url_for('static', filename=f'img/{default_image}')

class Media(db.Model):
    __abstract__ = True
    
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text)
    release_date = db.Column(db.Date)
    cover_image = db.Column(db.String(200))
    rating = db.Column(db.Float, default=0.0)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)

class Movie(Media):
    __tablename__ = 'movie'
    
    duration = db.Column(db.Integer)  # 电影时长（分钟）
    director = db.Column(db.String(100))
    genre = db.Column(db.String(50))
    imdb_id = db.Column(db.String(20), unique=True)
    imdb_rating = db.Column(db.Float)
    box_office = db.Column(db.String(50))  # 添加票房字段
    
    def __repr__(self):
        return f'<Movie {self.title}>'

    def to_dict(self):
        return {
            'id': self.id,
            'title': self.title,
            'description': self.description,
            'release_date': self.release_date.isoformat() if self.release_date else None,
            'duration': self.duration,
            'director': self.director,
            'genre': self.genre,
            'rating': self.rating,
            'imdb_rating': self.imdb_rating,
            'box_office': self.box_office,  # 添加到字典
            'user_id': self.user_id
        }
    def get_cover_url(self):
        return get_cover_url(self, 'default-movie.jpg')

    def format_duration(self):
        hours = self.duration // 60
        minutes = self.duration % 60
        if hours > 0:
            return f"{hours}小时 {minutes}分钟"
        return f"{minutes}分钟"

class Music(Media):
    __tablename__ = 'music'
    
    artist = db.Column(db.String(100))
    album = db.Column(db.String(200))
    duration = db.Column(db.Integer)  # 音乐时长（秒）
    genre = db.Column(db.String(50))
    spotify_id = db.Column(db.String(50), unique=True)
    
    def __repr__(self):
        return f'<Music {self.title}>'

    def to_dict(self):
        return {
            'id': self.id,
            'title': self.title,
            'artist': self.artist,
            'album': self.album,
            'description': self.description,
            'release_date': self.release_date.isoformat() if self.release_date else None,
            'duration': self.duration,
            'genre': self.genre,
            'rating': self.rating,
            'user_id': self.user_id
        }
    def get_cover_url(self):
        return get_cover_url(self, 'default-music.jpg')

    def format_duration(self):
        minutes = self.duration // 60
        seconds = self.duration % 60
        return f"{minutes}:{seconds:02d}"

class Game(Media):
    __tablename__ = 'game'
    
    developer = db.Column(db.String(100))
    publisher = db.Column(db.String(100))
    platform = db.Column(db.String(50))
    genre = db.Column(db.String(50))
    steam_id = db.Column(db.String(20), unique=True)
    steam_rating = db.Column(db.Float)
    
    def __repr__(self):
        return f'<Game {self.title}>'

    def to_dict(self):
        return {
            'id': self.id,
            'title': self.title,
            'description': self.description,
            'release_date': self.release_date.isoformat() if self.release_date else None,
            'developer': self.developer,
            'publisher': self.publisher,
            'platform': self.platform,
            'genre': self.genre,
            'rating': self.rating,
            'steam_rating': self.steam_rating,
            'user_id': self.user_id
        }
    def get_cover_url(self):
        return get_cover_url(self, 'default-game.jpg')

    def get_steam_url(self):
        if self.steam_id:
            return f"https://store.steampowered.com/app/{self.steam_id}"
        return None