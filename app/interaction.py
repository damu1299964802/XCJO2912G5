# app/models/interaction.py

from app import db
from datetime import datetime

class Like(db.Model):
    __tablename__ = 'likes'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    media_type = db.Column(db.String(20), nullable=False)  # 'movie', 'music', 'game'
    media_id = db.Column(db.Integer, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    __table_args__ = (
        db.UniqueConstraint('user_id', 'media_type', 'media_id', name='unique_like'),
    )

class Favorite(db.Model):
    __tablename__ = 'favorites'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    media_type = db.Column(db.String(20), nullable=False)  # 'movie', 'music', 'game'
    media_id = db.Column(db.Integer, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    __table_args__ = (
        db.UniqueConstraint('user_id', 'media_type', 'media_id', name='unique_favorite'),
    )

# 在 User 模型中添加关系
def update_user_model():
    from app.models.user import User
    User.likes = db.relationship('Like', backref='user', lazy='dynamic')
    User.favorites = db.relationship('Favorite', backref='user', lazy='dynamic')