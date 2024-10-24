<?php function threadedComments($comments, $options) {
    $commentClass = '';
    if ($comments->authorId) {
      if ($comments->authorId == $comments->ownerId) {
        $commentClass .= ' comment-by-author';
      } else {
        $commentClass .= ' comment-by-user';
        }
      }
    $commentLevelClass = $comments->levels > 0 ? ' comment-child' : ' comment-parent';
  ?>
  
  /* 自定义评论的代码结构 */
  <li id="li-<?php $comments->theId(); ?>" class="comment-body <?php echo $commentLevelClass;?>">
    <div class="media" id="<?php $comments->theId(); ?>">
       <?php $comments->gravatar(); ?>
       <div class="media-body">
         <?php $comments->author(); ?>
         <?php $comments->content(); ?>
       </div>  
       <div class="media-footer">
         <time><?php $comments->date('Y-m-d H:i'); ?></time>
         <?php $comments->reply('Reply'); ?>
       </div>
    </div>
    <?php if ($comments->children) { ?>
      <div class="comment-children">
         <?php $comments->threadedComments($options); ?>
      </div>
    <?php } ?>
  </li>
  
  <?php } ?>